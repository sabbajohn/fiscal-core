<?php

namespace freeline\FiscalCore\Adapters\NF;

use freeline\FiscalCore\Contracts\NotaFiscalInterface;
use freeline\FiscalCore\Adapters\NF\Builder\NotaFiscalBuilder;
use freeline\FiscalCore\Adapters\NF\Core\NotaFiscal;
use NFePHP\NFe\Tools;

/**
 * Adapter para NFe (modelo 55)
 * Integrado com sistema Composite + Builder
 */
class NFeAdapter implements NotaFiscalInterface
{
    private Tools $tools;

    public function __construct(Tools $tools)
    {
        $this->tools = $tools;
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
        $xml = $make->montaNFe();
        
        // Assina o XML
        $xmlAssinado = $this->tools->signNFe($xml);
        
        // Envia para SEFAZ
        return $this->tools->sefazEnviaLote([$xmlAssinado]);
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
        return $this->tools->sefazConsultaChave($chave);
    }

    public function cancelar(string $chave, string $motivo, string $protocolo): string
    {
        return $this->tools->sefazCancela($chave, $motivo, $protocolo);
    }

    public function inutilizar(int $ano, int $cnpj, int $modelo, int $serie, int $numeroInicial, int $numeroFinal, string $justificativa): string
    {
        // sped-nfe v5 usa (serie, numeroInicial, numeroFinal, justificativa, tpAmb, ano[2])
        $ano2Digitos = str_pad((string) ($ano % 100), 2, '0', STR_PAD_LEFT);
        return $this->tools->sefazInutiliza($serie, $numeroInicial, $numeroFinal, $justificativa, null, $ano2Digitos);
    }

    public function consultaNotasEmitidasParaEstabelecimento(int $ultimoNsu=0, int $numNSU=0, ?string $chave=null, string $fonte='AN'): string
    {
        return $this->tools->sefazDistDFe($ultimoNsu, $numNSU, $chave, $fonte);
    }

    public function sefazStatus(string $uf = '', ?int $ambiente = null, bool $ignorarContigencia = true): string
    {
        return $this->tools->sefazStatus($uf, $ambiente, $ignorarContigencia);
    }
}
