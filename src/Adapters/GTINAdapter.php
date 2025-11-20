<?php

namespace freeline\FiscalCore\Adapters;

use freeline\FiscalCore\Contracts\ProdutoInterface;
use freeline\FiscalCore\Support\CertificateManager;
use freeline\FiscalCore\Support\ConfigManager;
use NFePHP\Gtin\Gtin;
use NFePHP\Common\Certificate;
use stdClass;

use function PHPUnit\Framework\throwException;

class GTINAdapter implements ProdutoInterface
{
    private ?Certificate $certificate = null;
    private array $config = [];
    
    public function __construct()
    {
        $configManager = ConfigManager::getInstance();
        $this->config = $configManager->all();
        
        if ($configManager->isCertificateLoaded()) {
            $certManager = CertificateManager::getInstance();
            $this->certificate = $certManager->getCertificate();
        }
    }

	public function validarGTIN(string $codigo): bool
	{
		$codigo = trim($codigo);
		if ($codigo === '' || strtoupper($codigo) === 'SEM GTIN') {
			return true;
		}

		// Se a lib oficial estiver disponível, usa-a
		if (class_exists('NFePHP\\Gtin\\Gtin')) {
			try {
				/** @var object $checker */
				$checker = Gtin::check($codigo);
				if (method_exists($checker, 'isValid')) {
					return (bool) $checker->isValid();
				}
			} catch (\Throwable $e) {
				// fallback para algoritmo local
			}
		}

		// Fallback: validação local por dígito verificador (GTIN-8/12/13/14)
		if (!preg_match('/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $codigo)) {
			return false;
		}

		return self::checksumIsValid($codigo);
	}

	public function checkGTIN(string $codigo): self
	{
		$codigo = trim($codigo);
		if ($codigo === '' || strtoupper($codigo) === 'SEM GTIN') {
			return $this;
		}

		// Se a lib oficial estiver disponível, usa-a
		if (class_exists('NFePHP\\Gtin\\Gtin')) {
			try {
				/** @var object $checker */
				$checker = Gtin::check($codigo);
				return $this; // Retorna self para manter interface consistente
			} catch (\Throwable $e) {
				// fallback para algoritmo local
			}
		}

		// Fallback: validação local por dígito verificador (GTIN-8/12/13/14)
		if (!preg_match('/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $codigo)) {
			throw new \InvalidArgumentException("GTIN [$codigo] formato inválido.");
		}

		if (!self::checksumIsValid($codigo)) {
			throw new \InvalidArgumentException("GTIN [$codigo] dígito verificador inválido.");
		}

		return $this;
	}
    
    /**
     * Busca informações do produto por GTIN na base da Receita
     * Requer certificado digital para autenticação
     */
    public function buscarProduto(string $gtin): array
    {
        if (!$this->validarGTIN($gtin)) {
            throw new \InvalidArgumentException("GTIN inválido: {$gtin}");
        }
        
        if (!$this->certificate) {
            throw new \RuntimeException('Certificado digital necessário para busca de produtos. Use CertificateManager::getInstance()->loadFromFile()');
        }
        
        try {
            $checker = Gtin::check($gtin, $this->certificate);
            $response = $this->checkResponseStatus($checker->consulta());
            return (array) $response;
        } catch (\Exception $e) {
            throw new \RuntimeException('Erro na consulta do produto: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Consulta NCM e informações tributárias do produto
     */
    public function consultarNCM(string $gtin): array
    {
        $produto = $this->buscarProduto($gtin);
        
        return [
            'gtin' => $gtin,
            'ncm' => $produto['ncm'] ?? null,
            'cest' => $produto['cest'] ?? null,
            'descricao_ncm' => $this->obterDescricaoNCM($produto['ncm'] ?? ''),
            'aliquota_ipi' => null, // Seria obtido da tabela TIPI
            'origem' => null, // 0=Nacional, 1=Estrangeira, etc.
            'consultado_em' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Valida se o produto está ativo e pode ser comercializado
     */
    public function validarComercializacao(string $gtin): bool
    {
        if (!$this->validarGTIN($gtin)) {
            return false;
        }
        
        try {
            $produto = $this->buscarProduto($gtin);
            return $produto['status'] === 'ativo';
        } catch (\Exception $e) {
            return false; // Em caso de erro, considera inativo
        }
    }
    
    /**
     * Obtém descrição oficial do produto
     */
    public function obterDescricao(string $gtin): ?string
    {
        try {
            $produto = $this->buscarProduto($gtin);
            return $produto['descricao'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Helper para obter descrição do NCM
     */
    private function obterDescricaoNCM(string $ncm): ?string
    {
        if (empty($ncm)) {
            return null;
        }
        
        // Implementação futura: consulta à tabela NCM
        // Por enquanto, retorna formato padrão
        return "NCM {$ncm} - Consulte tabela oficial";
    }
	private static function checksumIsValid(string $gtin): bool
	{
		$digits = str_split($gtin);
		$checkDigit = (int) array_pop($digits);
		$digits = array_reverse($digits);
		$sum = 0;
		foreach ($digits as $i => $d) {
			$n = (int) $d;
			// pesos alternados 3 e 1 começando em 3 (a partir da direita)
			$sum += ($i % 2 === 0) ? ($n * 3) : $n;
		}
		$calc = (10 - ($sum % 10)) % 10;
		return $calc === $checkDigit;
	}

	private function checkResponseStatus(stdClass $response)
	{
		if($response->sucesso){
			return $response;
		}
		throwException(new \Exception("Erro na consulta do GTIN:CSTAT:{$response->cstat} -  {$response->motivo}"));
	}
}

