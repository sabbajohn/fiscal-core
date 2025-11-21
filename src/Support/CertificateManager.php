<?php

namespace freeline\FiscalCore\Support;

use NFePHP\Common\Certificate;
use NFePHP\Common\Exception\InvalidArgumentException;

/**
 * Singleton para gerenciamento de certificados digitais
 * 
 * Centraliza o carregamento e configuração de certificados A1 (.pfx)
 * para uso em operações fiscais (NFe, NFCe, NFSe)
 */
class CertificateManager
{
    private static ?self $instance = null;
    private ?Certificate $certificate = null;
    private ?string $certificateContent = null;
    private ?string $certificatePassword = null;
    private array $config = [];

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->loadCertificate();
        }
        return self::$instance;
    }

    /**
     * Carrega certificado a partir de arquivo .pfx
     */
    public function loadFromFile(string $pfxPath, string $password): self
    {
        if (!file_exists($pfxPath)) {
            throw new InvalidArgumentException("Certificado não encontrado: {$pfxPath}");
        }

        $content = file_get_contents($pfxPath);
        if ($content === false) {
            throw new InvalidArgumentException("Não foi possível ler o certificado: {$pfxPath}");
        }

        return $this->loadFromContent($content, $password);
    }

    /**
     * Carrega certificado a partir do conteúdo em string
     */
    public function loadFromContent(string $pfxContent, string $password): self
    {
        try {
            $this->certificate = Certificate::readPfx($pfxContent, $password);
            $this->certificateContent = $pfxContent;
            $this->certificatePassword = $password;

            // Carrega informações do certificado
            $this->loadCertificateInfo();

            return $this;
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Erro ao carregar certificado: " . $e->getMessage(), 0, $e);
        }
    }

    public static function isLoaded(): bool
    {
        $instance = self::getInstance();
        return $instance->certificate !== null;
    }
    
    public static function reload(): void
    {
        $instance = self::getInstance();
        $instance->clear();
        $instance->loadCertificate();
    }

    private function loadCertificate(): void
    {
        $certPath = $_ENV['FISCAL_CERT_PATH'] ?? getenv('FISCAL_CERT_PATH');
        $certPassword = $_ENV['FISCAL_CERT_PASSWORD'] ?? getenv('FISCAL_CERT_PASSWORD');
        
        // Sempre armazenar o caminho e senha se disponíveis
        // if ($certPath) {
        //     $this->set('certificado.cert_path', $certPath);
        // }
        // if ($certPassword) {
        //     $this->set('certificado.cert_password', $certPassword);
        // }
        
        if ($certPath && $certPassword && file_exists($certPath)) {
            try {
                // Usar CertificateManager para carregar o certificado
                $certManager = CertificateManager::getInstance();
                $certManager->loadFromFile($certPath, $certPassword);
                
                // Certificado carregado com sucesso
                // $this->set('certificado.carregado', true);
                // $this->set('certificado.erro', null);
                
                // Armazenar informações do certificado
                // $this->set('certificado.cnpj', $certManager->getCnpj());
                // $this->set('certificado.razao_social', $certManager->getRazaoSocial());
                
                $expirationDate = $certManager->getExpirationDate();
                if ($expirationDate) {
                    // $this->set('certificado.valido_ate', $expirationDate->format('Y-m-d'));
                    // $this->set('certificado.dias_restantes', $certManager->getDaysUntilExpiration());
                }
                
                // $this->set('certificado.valido', $certManager->isValid());
                
            } catch (\Exception $e) {
                // Log silencioso do erro - não quebra a aplicação
                // $this->set('certificado.erro', $e->getMessage());
                // $this->set('certificado.carregado', false);
            }
        } else {
            // Certificado não configurado ou arquivo não existe
            // if ($certPath && !file_exists($certPath)) {
            //     // $this->set('certificado.erro', "Arquivo de certificado não encontrado: {$certPath}");
            // } elseif (!$certPath) {
            //     $this->set('certificado.erro', 'FISCAL_CERT_PATH não configurado');
            // } elseif (!$certPassword) {
            //     $this->set('certificado.erro', 'FISCAL_CERT_PASSWORD não configurado');
            // }
            // $this->set('certificado.carregado', false);
        }
    }

    /**
     * Retorna a instância do certificado NFePHP
     */
    public function getCertificate(): ?Certificate
    {
        return $this->certificate;
    }

    /**
     * Retorna o conteúdo original do certificado
     */
    public function getCertificateContent(): ?string
    {
        return $this->certificateContent;
    }

    /**
     * Retorna a senha do certificado
     */
    public function getCertificatePassword(): ?string
    {
        return $this->certificatePassword;
    }

    /**
     * Retorna informações do certificado
     */
    public function getCertificateInfo(): array
    {
        return $this->config;
    }

    /**
     * Retorna o CNPJ do certificado
     */
    public function getCnpj(): ?string
    {
        return $this->config['cnpj'] ?? null;
    }

    /**
     * Retorna a razão social do certificado
     */
    public function getRazaoSocial(): ?string
    {
        return $this->config['razao_social'] ?? null;
    }

    /**
     * Verifica se o certificado está válido (não expirado)
     */
    public function isValid(): bool
    {
        if (!$this->certificate) {
            return false;
        }

        $validTo = $this->config['valid_to'] ?? null;
        if (!$validTo) {
            return false;
        }

        return time() < $validTo;
    }

    /**
     * Retorna quantos dias restam para expiração
     */
    public function getDaysUntilExpiration(): ?int
    {
        $validTo = $this->config['valid_to'] ?? null;
        if (!$validTo) {
            return null;
        }

        $diff = $validTo - time();
        return max(0, (int) ceil($diff / 86400)); // 86400 = segundos em um dia
    }

    /**
     * Limpa o certificado carregado
     */
    public function clear(): self
    {
        $this->certificate = null;
        $this->certificateContent = null;
        $this->certificatePassword = null;
        $this->config = [];
        return $this;
    }

    /**
     * Carrega informações detalhadas do certificado
     */
    private function loadCertificateInfo(): void
    {
        if (!$this->certificate) {
            return;
        }

        try {
            
            $this->config = [
                'cnpj' => $this->certificate->getCnpj() ?? $this->certificate->getCpf(),
                'razao_social' => $this->certificate->getCompanyName(),
                'valid_from' => $this->certificate->getValidFrom()->getTimestamp(),
                'valid_to' => $this->certificate->getValidTo()->getTimestamp(),
                'issuer' => $this->certificate->getCSP(),
            ];
        } catch (\Exception $e) {
            // Se falhar, mantém array vazio
            $this->config = [];
        }
    }

    public function getExpirationDate(): ?\DateTime
    {
        if (!$this->certificate) {
            return null;
        }
        return $this->certificate->getValidTo();
    }

    // Previne clonagem e serialização
    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}