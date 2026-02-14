<?php

namespace freeline\FiscalCore\Providers\NFSe;

use DOMDocument;
use DOMElement;
use freeline\FiscalCore\Exceptions\ValidationException;

/**
 * Provider para municípios que usam padrão DSF
 * 
 * Especificação: DSF (Declaração de Serviços Fiscais) v2.03/v2.04
 * Namespace: http://localhost:8080/WsNFe2/lote
 * 
 * Municípios suportados:
 * - Belém/PA (1501402)
 * - São Luís/MA
 * - Teresina/PI
 * - Outros municípios com padrão DSF
 * 
 * @package freeline\FiscalCore\Providers\NFSe
 */
class DsfProvider extends AbstractNFSeProvider
{
    /**
     * Namespace do padrão DSF
     */
    protected const NAMESPACE_LOTE = 'http://localhost:8080/WsNFe2/lote';
    protected const NAMESPACE_TIPOS = 'http://localhost:8080/WsNFe2/tp';
    protected const NAMESPACE_RPS = 'http://localhost:8080/WsNFe2/rps';
    
    /**
     * Versão do schema
     */
    protected const VERSAO = '2.04';
    
    /**
     * Código SIAFI do município
     * Belém/PA = 0427
     */
    protected string $codigoSiafi = '0427';
    
    /**
     * Código IBGE do município
     */
    protected string $codigoIbge = '1501402';

    public function __construct(array $config)
    {
        parent::__construct($config);

        if (!empty($config['codigo_siafi'])) {
            $this->codigoSiafi = str_pad((string) $config['codigo_siafi'], 4, '0', STR_PAD_LEFT);
        }

        if (!empty($config['codigo_municipio'])) {
            $this->codigoIbge = str_pad((string) $config['codigo_municipio'], 7, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function getVersao(): string
    {
        return self::VERSAO;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getCodigoMunicipio(): string
    {
        return $this->codigoIbge;
    }
    
    /**
     * Define o código SIAFI do município
     * 
     * @param string $codigo Código SIAFI (4 dígitos)
     * @return self
     */
    public function setCodigoSiafi(string $codigo): self
    {
        $this->codigoSiafi = str_pad($codigo, 4, '0', STR_PAD_LEFT);
        return $this;
    }
    
    /**
     * Define o código IBGE do município
     * 
     * @param string $codigo Código IBGE (7 dígitos)
     * @return self
     */
    public function setCodigoIbge(string $codigo): self
    {
        $this->codigoIbge = str_pad($codigo, 7, '0', STR_PAD_LEFT);
        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    protected function montarXmlRps(array $dados): string
    {
        $this->validarDadosBasicos($dados);
        
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;
        
        // Elemento raiz: ReqEnvioLoteRPS
        $req = $dom->createElementNS(self::NAMESPACE_LOTE, 'ReqEnvioLoteRPS');
        $req->setAttribute('xmlns:tipos', self::NAMESPACE_TIPOS);
        $req->setAttribute('xmlns:rps', self::NAMESPACE_RPS);
        $dom->appendChild($req);
        
        // Cabeçalho
        $cabecalho = $dom->createElement('Cabecalho');
        $req->appendChild($cabecalho);
        
        // CodCidade (SIAFI)
        $codCidade = $dom->createElement('CodCidade', $this->codigoSiafi);
        $cabecalho->appendChild($codCidade);
        
        // CPFCNPJRemetente
        $cpfCnpj = $dom->createElement('CPFCNPJRemetente');
        $cnpj = $dom->createElement('CNPJ', $dados['prestador']['cnpj']);
        $cpfCnpj->appendChild($cnpj);
        $cabecalho->appendChild($cpfCnpj);
        
        // RazaoSocialRemetente
        $razao = $dom->createElement(
            'RazaoSocialRemetente',
            $dados['prestador']['razao_social'] ?? ''
        );
        $cabecalho->appendChild($razao);
        
        // Transação (true para processar tudo ou nada)
        $transacao = $dom->createElement('transacao', 'true');
        $cabecalho->appendChild($transacao);
        
        // Período
        $dtInicio = $dom->createElement('dtInicio', date('Y-m-d', strtotime($dados['data_emissao'] ?? 'now')));
        $cabecalho->appendChild($dtInicio);
        
        $dtFim = $dom->createElement('dtFim', date('Y-m-d', strtotime($dados['data_emissao'] ?? 'now')));
        $cabecalho->appendChild($dtFim);
        
        // QtdRPS
        $qtdRps = $dom->createElement('QtdRPS', '1');
        $cabecalho->appendChild($qtdRps);
        
        // ValorTotalServicos
        $valorTotal = $dom->createElement('ValorTotalServicos', number_format($dados['valor_servicos'], 2, '.', ''));
        $cabecalho->appendChild($valorTotal);
        
        // ValorTotalDeducoes
        if (!empty($dados['valor_deducoes'])) {
            $valorDeducoes = $dom->createElement('ValorTotalDeducoes', number_format($dados['valor_deducoes'], 2, '.', ''));
            $cabecalho->appendChild($valorDeducoes);
        }
        
        // Versão
        $versao = $dom->createElement('Versao', '1');
        $cabecalho->appendChild($versao);
        
        // RPS
        $this->addRps($dom, $req, $dados);
        
        return $dom->saveXML();
    }
    
    /**
     * Adiciona RPS ao lote
     */
    protected function addRps(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        $rps = $dom->createElement('RPS');
        $rps->setAttribute('Id', 'rps:' . ($dados['numero'] ?? '1'));
        $parent->appendChild($rps);
        
        // Assinatura (será adicionada depois)
        // <Signature>...</Signature>
        
        // ChaveRPS
        $chaveRps = $dom->createElement('ChaveRPS');
        $rps->appendChild($chaveRps);
        
        $inscricao = $dom->createElement('InscricaoPrestador', $dados['prestador']['inscricao_municipal'] ?? '');
        $chaveRps->appendChild($inscricao);
        
        $serieRps = $dom->createElement('SerieRPS', $dados['serie'] ?? 'A');
        $chaveRps->appendChild($serieRps);
        
        $numeroRps = $dom->createElement('NumeroRPS', $dados['numero'] ?? '1');
        $chaveRps->appendChild($numeroRps);
        
        // TipoRPS (1=RPS, 2=Nota Conjugada, 3=Cupom)
        $tipoRps = $dom->createElement('TipoRPS', $dados['tipo'] ?? '1');
        $rps->appendChild($tipoRps);
        
        // DataEmissao
        $dataEmissao = $dom->createElement(
            'DataEmissao',
            date('Y-m-d', strtotime($dados['data_emissao'] ?? 'now'))
        );
        $rps->appendChild($dataEmissao);
        
        // StatusRPS (N=Normal, C=Cancelado)
        $statusRps = $dom->createElement('StatusRPS', $dados['status'] ?? 'N');
        $rps->appendChild($statusRps);
        
        // TributacaoRPS (T=Tributado no município, F=Tributado fora, I=Isento, J=ISS Suspenso)
        $tributacao = $dom->createElement('TributacaoRPS', $dados['tributacao'] ?? 'T');
        $rps->appendChild($tributacao);
        
        // ValorServicos
        $valorServicos = $dom->createElement('ValorServicos', number_format($dados['valor_servicos'], 2, '.', ''));
        $rps->appendChild($valorServicos);
        
        // ValorDeducoes (opcional)
        if (!empty($dados['valor_deducoes'])) {
            $valorDeducoes = $dom->createElement('ValorDeducoes', number_format($dados['valor_deducoes'], 2, '.', ''));
            $rps->appendChild($valorDeducoes);
        }
        
        // CodigoServico (LC 116/2003)
        $codigoServico = $dom->createElement('CodigoServico', $dados['codigo_servico'] ?? '01.01');
        $rps->appendChild($codigoServico);
        
        // AliquotaServicos (percentual)
        $aliquota = $dom->createElement('AliquotaServicos', number_format($dados['aliquota'] ?? 0, 4, '.', ''));
        $rps->appendChild($aliquota);
        
        // ISSRetido (true/false)
        $issRetido = $dom->createElement('ISSRetido', !empty($dados['iss_retido']) ? 'true' : 'false');
        $rps->appendChild($issRetido);
        
        // CPFCNPJTomador
        if (!empty($dados['tomador'])) {
            $cpfCnpjTomador = $dom->createElement('CPFCNPJTomador');
            
            if (!empty($dados['tomador']['cnpj'])) {
                $cnpj = $dom->createElement('CNPJ', $dados['tomador']['cnpj']);
                $cpfCnpjTomador->appendChild($cnpj);
            } elseif (!empty($dados['tomador']['cpf'])) {
                $cpf = $dom->createElement('CPF', $dados['tomador']['cpf']);
                $cpfCnpjTomador->appendChild($cpf);
            }
            
            $rps->appendChild($cpfCnpjTomador);
        }
        
        // RazaoSocialTomador
        if (!empty($dados['tomador']['razao_social'])) {
            $razaoTomador = $dom->createElement('RazaoSocialTomador', substr($dados['tomador']['razao_social'], 0, 115));
            $rps->appendChild($razaoTomador);
        }
        
        // Endereço Tomador
        if (!empty($dados['tomador']['endereco'])) {
            $this->addEnderecoTomador($dom, $rps, $dados);
        }
        
        // EmailTomador
        if (!empty($dados['tomador']['email'])) {
            $email = $dom->createElement('EmailTomador', $dados['tomador']['email']);
            $rps->appendChild($email);
        }
        
        // Discriminacao
        $discriminacao = $dom->createElement('Discriminacao');
        $discriminacao->appendChild($dom->createCDATASection($dados['discriminacao'] ?? ''));
        $rps->appendChild($discriminacao);
    }
    
    /**
     * Adiciona endereço do tomador
     */
    protected function addEnderecoTomador(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        if (!empty($dados['tomador']['endereco']['logradouro'])) {
            $logradouro = $dom->createElement(
                'EnderecoTomador',
                substr($dados['tomador']['endereco']['logradouro'], 0, 125)
            );
            $parent->appendChild($logradouro);
        }
        
        if (!empty($dados['tomador']['endereco']['numero'])) {
            $numero = $dom->createElement('NumeroEnderecoTomador', $dados['tomador']['endereco']['numero']);
            $parent->appendChild($numero);
        }
        
        if (!empty($dados['tomador']['endereco']['complemento'])) {
            $complemento = $dom->createElement(
                'ComplementoEnderecoTomador',
                substr($dados['tomador']['endereco']['complemento'], 0, 60)
            );
            $parent->appendChild($complemento);
        }
        
        if (!empty($dados['tomador']['endereco']['bairro'])) {
            $bairro = $dom->createElement('BairroTomador', substr($dados['tomador']['endereco']['bairro'], 0, 60));
            $parent->appendChild($bairro);
        }
        
        if (!empty($dados['tomador']['endereco']['codigo_municipio'])) {
            $cidade = $dom->createElement('CidadeTomador', $dados['tomador']['endereco']['codigo_municipio']);
            $parent->appendChild($cidade);
        }
        
        if (!empty($dados['tomador']['endereco']['uf'])) {
            $uf = $dom->createElement('CidadeTomadorDescricao', $dados['tomador']['endereco']['uf']);
            $parent->appendChild($uf);
        }
        
        if (!empty($dados['tomador']['endereco']['cep'])) {
            $cep = $dom->createElement('CEPTomador', preg_replace('/\D/', '', $dados['tomador']['endereco']['cep']));
            $parent->appendChild($cep);
        }
    }
    
    /**
     * Valida dados básicos
     */
    protected function validarDadosBasicos(array $dados): void
    {
        if (empty($dados['prestador']['cnpj'])) {
            throw new ValidationException('CNPJ do prestador é obrigatório');
        }
        
        if (empty($dados['prestador']['inscricao_municipal'])) {
            throw new ValidationException('Inscrição municipal do prestador é obrigatória');
        }

        if (empty($dados['prestador']['razao_social'])) {
            throw new ValidationException('Razão social do prestador é obrigatória');
        }
        
        if (empty($dados['valor_servicos'])) {
            throw new ValidationException('Valor dos serviços é obrigatório');
        }
        
        if (empty($dados['codigo_servico'])) {
            throw new ValidationException('Código do serviço (LC 116) é obrigatório');
        }
        
        if (empty($dados['discriminacao'])) {
            throw new ValidationException('Discriminação do serviço é obrigatória');
        }
    }
    
    /**
     * {@inheritDoc}
     */
    protected function processarResposta(string $xmlResposta): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlResposta);
        
        $response = [
            'sucesso' => false,
            'mensagem' => '',
            'dados' => []
        ];
        
        // Verificar erros
        $erros = $dom->getElementsByTagName('Erro');
        if ($erros->length > 0) {
            $mensagens = [];
            foreach ($erros as $erro) {
                $codigo = $erro->getElementsByTagName('Codigo')->item(0)?->nodeValue ?? '';
                $descricao = $erro->getElementsByTagName('Descricao')->item(0)?->nodeValue ?? '';
                
                $mensagens[] = "[$codigo] $descricao";
            }
            
            $response['mensagem'] = implode('; ', $mensagens);
            return $response;
        }
        
        // Sucesso - extrair ChaveNFSeRPS
        $chaveNfse = $dom->getElementsByTagName('ChaveNFSeRPS')->item(0);
        if ($chaveNfse) {
            $response['sucesso'] = true;
            $response['mensagem'] = 'NFSe gerada com sucesso';
            
            $response['dados'] = [
                'numero' => $chaveNfse->getElementsByTagName('NumeroNFSe')->item(0)?->nodeValue ?? '',
                'codigo_verificacao' => $chaveNfse->getElementsByTagName('CodigoVerificacao')->item(0)?->nodeValue ?? '',
                'inscricao_prestador' => $chaveNfse->getElementsByTagName('InscricaoPrestador')->item(0)?->nodeValue ?? '',
            ];
        }
        
        return $response;
    }
    
    /**
     * Consulta lote de RPS
     */
    public function consultarLote(string $numeroLote): array
    {
        // TODO: Implementar consulta de lote
        return [
            'sucesso' => false,
            'mensagem' => 'Método não implementado',
            'dados' => []
        ];
    }
    
    /**
     * Cancela NFSe
     */
    public function cancelarNfse(array $dados): array
    {
        // TODO: Implementar cancelamento
        return [
            'sucesso' => false,
            'mensagem' => 'Método não implementado',
            'dados' => []
        ];
    }
}
