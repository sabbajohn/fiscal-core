<?php

namespace freeline\FiscalCore\Providers\NFSe;

use DOMDocument;
use DOMElement;
use freeline\FiscalCore\Exceptions\ValidationException;

/**
 * Provider para municípios que usam padrão EGOVERNE
 * 
 * Especificação: EGOVERNE (Curitiba)
 * Namespace: http://isscuritiba.curitiba.pr.gov.br/iss/nfse.xsd
 * 
 * Municípios suportados:
 * - Curitiba/PR (4106902)
 * 
 * @package freeline\FiscalCore\Providers\NFSe
 */
class EgoverneProvider extends AbstractNFSeProvider
{
    /**
     * Namespace do padrão EGOVERNE
     */
    protected const NAMESPACE_URI = 'http://isscuritiba.curitiba.pr.gov.br/iss/nfse.xsd';
    
    /**
     * Versão do schema
     */
    protected const VERSAO = '1.00';
    
    /**
     * Código do município (IBGE)
     */
    protected string $codigoMunicipio = '4106902';

    public function __construct(array $config)
    {
        parent::__construct($config);

        if (!empty($config['codigo_municipio'])) {
            $this->codigoMunicipio = str_pad((string) $config['codigo_municipio'], 7, '0', STR_PAD_LEFT);
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
        return $this->codigoMunicipio;
    }
    
    /**
     * Define o código do município
     * 
     * @param string $codigo Código IBGE (7 dígitos)
     * @return self
     */
    public function setCodigoMunicipio(string $codigo): self
    {
        $this->codigoMunicipio = str_pad($codigo, 7, '0', STR_PAD_LEFT);
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
        
        // Elemento raiz: EnviarLoteRpsEnvio
        $enviarLote = $dom->createElementNS(self::NAMESPACE_URI, 'EnviarLoteRpsEnvio');
        $enviarLote->setAttribute('xmlns', self::NAMESPACE_URI);
        $dom->appendChild($enviarLote);
        
        // LoteRps
        $loteRps = $dom->createElement('LoteRps');
        $loteRps->setAttribute('Id', 'lote' . ($dados['lote'] ?? '1'));
        $loteRps->setAttribute('versao', self::VERSAO);
        $enviarLote->appendChild($loteRps);
        
        // NumeroLote
        $numeroLote = $dom->createElement('NumeroLote', $dados['lote'] ?? '1');
        $loteRps->appendChild($numeroLote);
        
        // CnpjPrestador
        $cnpjPrestador = $dom->createElement('Cnpj', $dados['prestador']['cnpj']);
        $loteRps->appendChild($cnpjPrestador);
        
        // InscricaoMunicipal
        if (!empty($dados['prestador']['inscricao_municipal'])) {
            $im = $dom->createElement('InscricaoMunicipal', $dados['prestador']['inscricao_municipal']);
            $loteRps->appendChild($im);
        }
        
        // QuantidadeRps
        $qtdRps = $dom->createElement('QuantidadeRps', '1');
        $loteRps->appendChild($qtdRps);
        
        // ListaRps
        $listaRps = $dom->createElement('ListaRps');
        $loteRps->appendChild($listaRps);
        
        // Rps
        $rps = $this->criarRps($dom, $dados);
        $listaRps->appendChild($rps);
        
        return $dom->saveXML();
    }
    
    /**
     * Cria o elemento RPS
     */
    protected function criarRps(DOMDocument $dom, array $dados): DOMElement
    {
        $rps = $dom->createElement('Rps');
        
        $infDeclaracao = $dom->createElement('InfDeclaracaoPrestacaoServico');
        $infDeclaracao->setAttribute('Id', 'rps' . ($dados['numero'] ?? '1'));
        $rps->appendChild($infDeclaracao);
        
        // IdentificacaoRps
        $this->addIdentificacaoRps($dom, $infDeclaracao, $dados);
        
        // DataEmissao
        $dataEmissao = $dom->createElement(
            'DataEmissao',
            date('Y-m-d\TH:i:s', strtotime($dados['data_emissao'] ?? 'now'))
        );
        $infDeclaracao->appendChild($dataEmissao);
        
        // NaturezaOperacao
        $natureza = $dom->createElement('NaturezaOperacao', $dados['natureza_operacao'] ?? '1');
        $infDeclaracao->appendChild($natureza);
        
        // RegimeEspecialTributacao (opcional)
        if (!empty($dados['regime_tributacao'])) {
            $regime = $dom->createElement('RegimeEspecialTributacao', $dados['regime_tributacao']);
            $infDeclaracao->appendChild($regime);
        }
        
        // OptanteSimplesNacional
        $optante = $dom->createElement('OptanteSimplesNacional', $dados['optante_simples'] ?? '2');
        $infDeclaracao->appendChild($optante);
        
        // IncentivadorCultural
        $incentivo = $dom->createElement('IncentivadorCultural', $dados['incentivador_cultural'] ?? '2');
        $infDeclaracao->appendChild($incentivo);
        
        // Status
        $status = $dom->createElement('Status', $dados['status'] ?? '1');
        $infDeclaracao->appendChild($status);
        
        // Servico
        $this->addServico($dom, $infDeclaracao, $dados);
        
        // Prestador
        $this->addPrestador($dom, $infDeclaracao, $dados);
        
        // Tomador
        $this->addTomador($dom, $infDeclaracao, $dados);
        
        return $rps;
    }
    
    /**
     * Adiciona identificação do RPS
     */
    protected function addIdentificacaoRps(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        $rpsInfo = $dom->createElement('Rps');
        
        $identificacao = $dom->createElement('IdentificacaoRps');
        $rpsInfo->appendChild($identificacao);
        
        $numero = $dom->createElement('Numero', $dados['numero'] ?? '1');
        $identificacao->appendChild($numero);
        
        $serie = $dom->createElement('Serie', $dados['serie'] ?? 'A');
        $identificacao->appendChild($serie);
        
        $tipo = $dom->createElement('Tipo', $dados['tipo'] ?? '1');
        $identificacao->appendChild($tipo);
        
        $parent->appendChild($rpsInfo);
    }
    
    /**
     * Adiciona informações do serviço
     */
    protected function addServico(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        $servico = $dom->createElement('Servico');
        
        $valores = $dom->createElement('Valores');
        
        $valorServicos = $dom->createElement('ValorServicos', number_format($dados['valor_servicos'], 2, '.', ''));
        $valores->appendChild($valorServicos);
        
        if (!empty($dados['valor_deducoes'])) {
            $valorDeducoes = $dom->createElement('ValorDeducoes', number_format($dados['valor_deducoes'], 2, '.', ''));
            $valores->appendChild($valorDeducoes);
        }
        
        if (!empty($dados['valor_iss'])) {
            $valorIss = $dom->createElement('ValorIss', number_format($dados['valor_iss'], 2, '.', ''));
            $valores->appendChild($valorIss);
        }
        
        if (!empty($dados['iss_retido'])) {
            $issRetido = $dom->createElement('IssRetido', $dados['iss_retido'] ? '1' : '2');
            $valores->appendChild($issRetido);
        }
        
        $servico->appendChild($valores);
        
        $itemListaServico = $dom->createElement('ItemListaServico', $dados['codigo_servico'] ?? '01.01');
        $servico->appendChild($itemListaServico);
        
        if (!empty($dados['codigo_cnae'])) {
            $codigoCnae = $dom->createElement('CodigoCnae', $dados['codigo_cnae']);
            $servico->appendChild($codigoCnae);
        }
        
        if (!empty($dados['codigo_tributacao_municipio'])) {
            $codTrib = $dom->createElement('CodigoTributacaoMunicipio', $dados['codigo_tributacao_municipio']);
            $servico->appendChild($codTrib);
        }
        
        $discriminacao = $dom->createElement('Discriminacao');
        $discriminacao->appendChild($dom->createCDATASection($dados['discriminacao'] ?? ''));
        $servico->appendChild($discriminacao);
        
        $codigoMunicipio = $dom->createElement('CodigoMunicipio', $this->codigoMunicipio);
        $servico->appendChild($codigoMunicipio);
        
        if (!empty($dados['aliquota'])) {
            $aliquota = $dom->createElement('Aliquota', number_format($dados['aliquota'], 4, '.', ''));
            $servico->appendChild($aliquota);
        }
        
        $parent->appendChild($servico);
    }
    
    /**
     * Adiciona informações do prestador
     */
    protected function addPrestador(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        $prestador = $dom->createElement('Prestador');
        
        $cpfCnpj = $dom->createElement('CpfCnpj');
        $cnpj = $dom->createElement('Cnpj', $dados['prestador']['cnpj']);
        $cpfCnpj->appendChild($cnpj);
        $prestador->appendChild($cpfCnpj);
        
        if (!empty($dados['prestador']['inscricao_municipal'])) {
            $im = $dom->createElement('InscricaoMunicipal', $dados['prestador']['inscricao_municipal']);
            $prestador->appendChild($im);
        }
        
        $parent->appendChild($prestador);
    }
    
    /**
     * Adiciona informações do tomador
     */
    protected function addTomador(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        if (empty($dados['tomador'])) {
            return;
        }
        
        $tomador = $dom->createElement('Tomador');
        
        $identTomador = $dom->createElement('IdentificacaoTomador');
        $cpfCnpj = $dom->createElement('CpfCnpj');
        
        if (!empty($dados['tomador']['cnpj'])) {
            $cnpj = $dom->createElement('Cnpj', $dados['tomador']['cnpj']);
            $cpfCnpj->appendChild($cnpj);
        } elseif (!empty($dados['tomador']['cpf'])) {
            $cpf = $dom->createElement('Cpf', $dados['tomador']['cpf']);
            $cpfCnpj->appendChild($cpf);
        }
        
        $identTomador->appendChild($cpfCnpj);
        $tomador->appendChild($identTomador);
        
        if (!empty($dados['tomador']['razao_social'])) {
            $razao = $dom->createElement('RazaoSocial', substr($dados['tomador']['razao_social'], 0, 115));
            $tomador->appendChild($razao);
        }
        
        if (!empty($dados['tomador']['endereco'])) {
            $endereco = $dom->createElement('Endereco');
            
            if (!empty($dados['tomador']['endereco']['logradouro'])) {
                $logradouro = $dom->createElement('Endereco', substr($dados['tomador']['endereco']['logradouro'], 0, 125));
                $endereco->appendChild($logradouro);
            }
            
            if (!empty($dados['tomador']['endereco']['numero'])) {
                $numero = $dom->createElement('Numero', substr($dados['tomador']['endereco']['numero'], 0, 10));
                $endereco->appendChild($numero);
            }
            
            if (!empty($dados['tomador']['endereco']['complemento'])) {
                $complemento = $dom->createElement('Complemento', substr($dados['tomador']['endereco']['complemento'], 0, 60));
                $endereco->appendChild($complemento);
            }
            
            if (!empty($dados['tomador']['endereco']['bairro'])) {
                $bairro = $dom->createElement('Bairro', substr($dados['tomador']['endereco']['bairro'], 0, 60));
                $endereco->appendChild($bairro);
            }
            
            if (!empty($dados['tomador']['endereco']['codigo_municipio'])) {
                $codigoMunicipio = $dom->createElement('CodigoMunicipio', $dados['tomador']['endereco']['codigo_municipio']);
                $endereco->appendChild($codigoMunicipio);
            }
            
            if (!empty($dados['tomador']['endereco']['uf'])) {
                $uf = $dom->createElement('Uf', $dados['tomador']['endereco']['uf']);
                $endereco->appendChild($uf);
            }
            
            if (!empty($dados['tomador']['endereco']['cep'])) {
                $cep = $dom->createElement('Cep', preg_replace('/\D/', '', $dados['tomador']['endereco']['cep']));
                $endereco->appendChild($cep);
            }
            
            $tomador->appendChild($endereco);
        }
        
        if (!empty($dados['tomador']['telefone']) || !empty($dados['tomador']['email'])) {
            $contato = $dom->createElement('Contato');
            
            if (!empty($dados['tomador']['telefone'])) {
                $telefone = $dom->createElement('Telefone', substr($dados['tomador']['telefone'], 0, 20));
                $contato->appendChild($telefone);
            }
            
            if (!empty($dados['tomador']['email'])) {
                $email = $dom->createElement('Email', substr($dados['tomador']['email'], 0, 80));
                $contato->appendChild($email);
            }
            
            $tomador->appendChild($contato);
        }
        
        $parent->appendChild($tomador);
    }
    
    /**
     * Valida dados básicos
     */
    protected function validarDadosBasicos(array $dados): void
    {
        if (empty($dados['prestador']['cnpj'])) {
            throw new ValidationException('CNPJ do prestador é obrigatório');
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
        $listaMensagem = $dom->getElementsByTagName('MensagemRetorno');
        if ($listaMensagem->length > 0) {
            $mensagens = [];
            foreach ($listaMensagem as $msg) {
                $codigo = $msg->getElementsByTagName('Codigo')->item(0)?->nodeValue ?? '';
                $mensagem = $msg->getElementsByTagName('Mensagem')->item(0)?->nodeValue ?? '';
                $correcao = $msg->getElementsByTagName('Correcao')->item(0)?->nodeValue ?? '';
                
                $mensagens[] = "[$codigo] $mensagem" . ($correcao ? " - $correcao" : '');
            }
            
            $response['mensagem'] = implode('; ', $mensagens);
            return $response;
        }
        
        // Sucesso - extrair dados da NFSe
        $nfse = $dom->getElementsByTagName('InfNfse')->item(0);
        if ($nfse) {
            $response['sucesso'] = true;
            $response['mensagem'] = 'NFSe gerada com sucesso';
            
            $response['dados'] = [
                'numero' => $nfse->getElementsByTagName('Numero')->item(0)?->nodeValue ?? '',
                'codigo_verificacao' => $nfse->getElementsByTagName('CodigoVerificacao')->item(0)?->nodeValue ?? '',
                'data_emissao' => $nfse->getElementsByTagName('DataEmissao')->item(0)?->nodeValue ?? '',
            ];
        }
        
        return $response;
    }
    
    /**
     * Consulta NFSe por RPS
     */
    public function consultarNfsePorRps(array $dados): array
    {
        // TODO: Implementar consulta por RPS
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
