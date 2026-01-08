<?php

namespace freeline\FiscalCore\Facade;

use freeline\FiscalCore\Adapters\NF\NFeAdapter;
use freeline\FiscalCore\Adapters\ImpressaoAdapter;
use freeline\FiscalCore\Support\ResponseHandler;
use freeline\FiscalCore\Support\FiscalResponse;
use freeline\FiscalCore\Adapters\NF\Builder\NotaFiscalBuilder;
use freeline\FiscalCore\Adapters\NF\Core\NotaFiscal;
use freeline\FiscalCore\Support\ToolsFactory;
use NFePHP\NFe\Tools;

/**
 * Facade para NFe - Interface simplificada e com tratamento de erros
 * Evita que aplicações recebam erros 500 fornecendo responses padronizados
 */
class NFeFacade
{
    private NFeAdapter $nfe;
    private ImpressaoAdapter $impressao;
    private ResponseHandler $responseHandler;

    public function __construct(
        ?NFeAdapter $nfe = null,
        ?ImpressaoAdapter $impressao = null
    ) {
        $this->responseHandler = new ResponseHandler();
        
        try {
            $this->nfe = $nfe ?? new NFeAdapter(ToolsFactory::createNFeTools());
            $this->impressao = $impressao ?? new ImpressaoAdapter();
        } catch (\Exception $e) {
            // Se não conseguir inicializar, deixa null - será tratado nos métodos
        }
    }

    /**
     * Emite uma NFe com tratamento completo de erros
     * 
     * @param array $dados Dados da nota fiscal
     * @return FiscalResponse Response padronizado com sucesso/erro
     */
    public function emitir(array $dados): FiscalResponse
    {
        return $this->responseHandler->handle(function() use ($dados) {
            if (!isset($this->nfe)) {
                throw new \RuntimeException('NFe adapter não inicializado. Verifique configuração de certificado.');
            }
            
            // Garante que é modelo 55 (NFe)
            if (!isset($dados['identificacao']['mod'])) {
                $dados['identificacao']['mod'] = 55;
            }
            
            $result = $this->nfe->emitir($dados);
            
            return [
                'xml_response' => $result,
                'modelo' => 55,
                'ambiente' => $dados['identificacao']['tpAmb'] ?? 2,
                'chave_acesso' => $this->extrairChaveAcesso($result)
            ];
        }, 'emissao_nfe');
    }

    /**
     * Consulta uma NFe pelo número da chave
     * 
     * @param string $chave Chave de acesso da NFe
     * @return FiscalResponse Response padronizado
     */
    public function consultar(string $chave): FiscalResponse
    {
        return $this->responseHandler->handle(function() use ($chave) {
            if (!isset($this->nfe)) {
                throw new \RuntimeException('NFe adapter não inicializado.');
            }
            
            if (strlen($chave) !== 44) {
                throw new \InvalidArgumentException('Chave de acesso deve ter 44 dígitos');
            }
            
            $result = $this->nfe->consultar($chave);
            
            return [
                'xml_response' => $result,
                'chave_acesso' => $chave,
                'situacao' => $this->extrairSituacao($result)
            ];
        }, 'consulta_nfe');
    }

    /**
     * Cancela uma NFe
     * 
     * @param string $chave Chave de acesso
     * @param string $motivo Motivo do cancelamento
     * @param string $protocolo Protocolo de autorização
     * @return FiscalResponse Response padronizado
     */
    public function cancelar(string $chave, string $motivo, string $protocolo): FiscalResponse
    {
        return $this->responseHandler->handle(function() use ($chave, $motivo, $protocolo) {
            if (!isset($this->nfe)) {
                throw new \RuntimeException('NFe adapter não inicializado.');
            }
            
            if (strlen($motivo) < 15) {
                throw new \InvalidArgumentException('Motivo deve ter pelo menos 15 caracteres');
            }
            
            $success = $this->nfe->cancelar($chave, $motivo, $protocolo);
            
            return [
                'cancelado' => $success,
                'chave_acesso' => $chave,
                'motivo' => $motivo,
                'protocolo' => $protocolo
            ];
        }, 'cancelamento_nfe');
    }

    /**
     * Inutiliza numeração da NFe
     */
    public function inutilizar(
        int $ano, 
        int $cnpj, 
        int $serie, 
        int $numeroInicial, 
        int $numeroFinal, 
        string $justificativa
    ): FiscalResponse {
        return $this->responseHandler->handle(function() use (
            $ano, $cnpj, $serie, $numeroInicial, $numeroFinal, $justificativa
        ) {
            if (!isset($this->nfe)) {
                throw new \RuntimeException('NFe adapter não inicializado.');
            }
            
            if (strlen($justificativa) < 15) {
                throw new \InvalidArgumentException('Justificativa deve ter pelo menos 15 caracteres');
            }
            
            $success = $this->nfe->inutilizar(
                $ano, $cnpj, 55, $serie, 
                $numeroInicial, $numeroFinal, $justificativa
            );
            
            return [
                'inutilizado' => $success,
                'serie' => $serie,
                'numeros' => [
                    'inicial' => $numeroInicial,
                    'final' => $numeroFinal
                ],
                'justificativa' => $justificativa
            ];
        }, 'inutilizacao_nfe');
    }

    /**
     * Verifica status do serviço SEFAZ
     * 
     * @param string $uf UF para consulta
     * @param int|null $ambiente Ambiente (1=produção, 2=homologação)
     * @return FiscalResponse Response com status do serviço
     */
    public function verificarStatusSefaz(string $uf = '', ?int $ambiente = null): FiscalResponse
    {
        return $this->responseHandler->handle(function() use ($uf, $ambiente) {
            if (!isset($this->nfe)) {
                throw new \RuntimeException('NFe adapter não inicializado.');
            }
            
            $result = $this->nfe->sefazStatus($uf, $ambiente);
            
            return [
                'xml_response' => $result,
                'uf' => $uf ?: 'SC',
                'ambiente' => $ambiente ?: 2,
                'status' => $this->extrairStatusSefaz($result)
            ];
        }, 'status_sefaz');
    }

    /**
     * Gera DANFE da NFe
     * 
     * @param string $xmlAutorizado XML da NFe autorizada
     * @return FiscalResponse Response com o PDF ou erro
     */
    public function gerarDanfe(string $xmlAutorizado): FiscalResponse
    {
        return $this->responseHandler->handle(function() use ($xmlAutorizado) {
            if (!isset($this->impressao)) {
                throw new \RuntimeException('Impressao adapter não disponível.');
            }
            
            if (empty($xmlAutorizado)) {
                throw new \InvalidArgumentException('XML autorizado é obrigatório');
            }
            
            $pdf = $this->impressao->gerarDanfe($xmlAutorizado);
            
            return [
                'pdf_base64' => base64_encode($pdf),
                'content_type' => 'application/pdf',
                'filename' => 'danfe_' . date('Ymd_His') . '.pdf'
            ];
        }, 'geracao_danfe');
    }

    /**
     * Construtor fluente para NFe
     * 
     * @return NotaFiscalBuilder Builder para construção type-safe
     */
    public static function builder(): NotaFiscalBuilder
    {
        return new NotaFiscalBuilder();
    }

    /**
     * Cria NFe a partir de array (sem emitir)
     * Útil para validação prévia
     * 
     * @param array $dados Dados da nota
     * @return FiscalResponse Response com objeto NotaFiscal
     */
    public function criarNota(array $dados): FiscalResponse
    {
        return $this->responseHandler->handle(function() use ($dados) {
            if (!isset($this->nfe)) {
                throw new \RuntimeException('NFe adapter não inicializado.');
            }
            
            if (!isset($dados['identificacao']['mod'])) {
                $dados['identificacao']['mod'] = 55;
            }
            
            $nota = $this->nfe->criarNota($dados);
            $nota->validate();
            
            return [
                'nota_fiscal' => $nota,
                'modelo' => 55,
                'validada' => true,
                'chave_simulada' => $this->simularChaveAcesso($dados)
            ];
        }, 'criacao_nota');
    }

    // Métodos auxiliares para extrair informações dos XMLs
    
    private function extrairChaveAcesso(string $xml): ?string
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            $xpath = new \DOMXPath($dom);
            $node = $xpath->query('//chNFe')->item(0);
            return $node ? $node->nodeValue : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function extrairSituacao(string $xml): string
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            $xpath = new \DOMXPath($dom);
            $node = $xpath->query('//xMotivo')->item(0);
            return $node ? $node->nodeValue : 'Status não identificado';
        } catch (\Exception $e) {
            return 'Erro ao extrair situação';
        }
    }
    
    private function extrairStatusSefaz(string $xml): string
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            $xpath = new \DOMXPath($dom);
            $cStat = $xpath->query('//cStat')->item(0);
            $xMotivo = $xpath->query('//xMotivo')->item(0);
            
            if ($cStat && $xMotivo) {
                return $cStat->nodeValue . ' - ' . $xMotivo->nodeValue;
            }
            return 'Status não identificado';
        } catch (\Exception $e) {
            return 'Erro ao extrair status';
        }
    }
    
    private function simularChaveAcesso(array $dados): string
    {
        // Simula chave baseada nos dados (para validação prévia)
        $uf = $dados['emitente']['endereco']['cUF'] ?? '42';
        $cnpj = preg_replace('/\D/', '', $dados['emitente']['CNPJ'] ?? '00000000000000');
        $modelo = '55';
        $serie = str_pad($dados['identificacao']['serie'] ?? '1', 3, '0', STR_PAD_LEFT);
        $numero = str_pad($dados['identificacao']['nNF'] ?? '1', 9, '0', STR_PAD_LEFT);
        $codigo = str_pad(rand(10000000, 99999999), 8, '0');
        
        $chave = $uf . date('ym') . $cnpj . $modelo . $serie . $numero . '1' . $codigo;
        $dv = $this->calcularDV($chave);
        
        return $chave . $dv;
    }
    
    private function calcularDV(string $chave): string
    {
        $soma = 0;
        $peso = 2;
        
        for ($i = strlen($chave) - 1; $i >= 0; $i--) {
            $soma += (int)$chave[$i] * $peso;
            $peso = $peso == 9 ? 2 : $peso + 1;
        }
        
        $dv = 11 - ($soma % 11);
        return $dv >= 10 ? '0' : (string)$dv;
    }
}