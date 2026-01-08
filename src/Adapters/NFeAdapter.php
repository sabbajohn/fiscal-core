<?php

namespace freeline\FiscalCore\Adapters;

use freeline\FiscalCore\Contracts\NotaFiscalInterface;
use freeline\FiscalCore\Adapters\NF\Builder\NotaFiscalBuilder;
use freeline\FiscalCore\Adapters\NF\Core\NotaFiscal;
use freeline\FiscalCore\Support\ToolsFactory;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Make;

/**
 * Adapter para NFe (modelo 55)
 * Integrado com sistema Composite + Builder
 */
class NFeAdapter implements NotaFiscalInterface
{
    private ?Tools $tools = null;
    private ?Tools $toolsWithoutCert = null;

    public function __construct()
    {
        // Não inicializa tools no construtor para permitir operações sem certificado
    }

    /**
     * Obtém instância de Tools que requer certificado
     */
    private function getTools(): Tools
    {
        if ($this->tools === null) {
            $this->tools = ToolsFactory::createNFeTools(true);
        }
        return $this->tools;
    }

    /**
     * Obtém instância de Tools sem exigir certificado (para consultas e status)
     */
    private function getToolsOptionalCert(): Tools
    {
        if ($this->toolsWithoutCert === null) {
            $this->toolsWithoutCert = ToolsFactory::createNFeTools(false);
        }
        return $this->toolsWithoutCert;
    }

    /**
     * Emite uma NFe a partir de array de dados
     * Usa o Builder para construir a nota de forma type-safe
     * 
     * @param array $dados Dados da nota fiscal
     * @return string Resposta da SEFAZ (XML do protocolo)
     * @throws \Exception Se houver erro na construção ou envio
     */
    public function emitir(array $dados): string
    {
        // Constrói a nota usando o Builder
        $nota = NotaFiscalBuilder::fromArray($dados)->build();
        
        // Valida a estrutura
        $nota->validate();
        
        // Obtém o objeto Make populado
        $make = $nota->getMake();
        
        // Monta o XML da NFe
        $xml = $make->getXML();
        $xml = $make->monta();
        
        // Assina o XML (requer certificado)
        $xmlAssinado = $this->getTools()->signNFe($xml);
        
        // Envia para SEFAZ (requer certificado)
        return $this->getTools()->sefazEnviaLote([$xmlAssinado]);
    }

    /**
     * Construtor fluente para NFe
     * Retorna NotaFiscalBuilder para construção incremental
     */
    public static function builder(): NotaFiscalBuilder
    {
        return new NotaFiscalBuilder();
    }

    /**
     * Cria NFe a partir de array e retorna o objeto NotaFiscal
     * Útil para manipulação antes do envio
     */
    public function criarNota(array $dados): NotaFiscal
    {
        return NotaFiscalBuilder::fromArray($dados)->build();
    }

    public function consultar(string $chave): string
    {
        // Consulta não requer certificado (apenas leitura)
        return $this->getToolsOptionalCert()->sefazConsultaChave($chave);
    }

    public function cancelar(string $chave, string $motivo, string $protocolo): bool
    {
        // Cancelamento requer certificado (assinatura)
        return $this->getTools()->sefazCancela($chave, $motivo, $protocolo);
    }

    public function inutilizar(int $ano, int $cnpj, int $modelo, int $serie, int $numeroInicial, int $numeroFinal, string $justificativa): bool
    {
        // Inutilização requer certificado (assinatura)
        return $this->getTools()->sefazInutiliza($ano, $cnpj, $modelo, $serie, $numeroInicial, $numeroFinal, $justificativa);
    }

    public function sefazStatus(string $uf = '', ?int $ambiente = null, bool $ignorarContigencia = true): string
    {
        // Status não requer certificado (apenas consulta de disponibilidade)
        return $this->getToolsOptionalCert()->sefazStatus($uf, $ambiente, $ignorarContigencia);
    }
}