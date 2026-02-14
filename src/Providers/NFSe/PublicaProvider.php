<?php

namespace freeline\FiscalCore\Providers\NFSe;

use DOMDocument;
use DOMElement;
use freeline\FiscalCore\Exceptions\ValidationException;

/**
 * Provider para municípios que usam padrão PUBLICA
 * 
 * Especificação: Schema NFSe v03 (Pública)
 * Namespace: http://www.publica.inf.br
 * 
 * Municípios suportados:
 * - Joinville/SC (4209102)
 * - Itajaí/SC (4208203)
 * - Outros municípios com padrão PUBLICA
 * 
 * @package freeline\FiscalCore\Providers\NFSe
 */
class PublicaProvider extends AbstractNFSeProvider
{
    /**
     * Namespace do padrão PUBLICA
     */
    protected const NAMESPACE_URI = 'http://www.publica.inf.br';
    
    /**
     * Versão do schema
     */
    protected const VERSAO = '3.00';
    
    /**
     * Código do município (IBGE)
     */
    protected string $codigoMunicipio = '4209102'; // Joinville por padrão

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
        // Validação inicial
        $this->validarDadosBasicos($dados);
        
        // Criar documento XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;
        
        // Elemento raiz: EnviarLoteRpsE envio
        $enviarLoteRps = $dom->createElementNS(self::NAMESPACE_URI, 'EnviarLoteRpsEnvio');
        $enviarLoteRps->setAttribute('xmlns', self::NAMESPACE_URI);
        $dom->appendChild($enviarLoteRps);
        
        // LoteRps
        $loteRps = $dom->createElement('LoteRps');
        $loteRps->setAttribute('Id', 'lote' . ($dados['lote'] ?? '1'));
        $loteRps->setAttribute('versao', self::VERSAO);
        $enviarLoteRps->appendChild($loteRps);
        
        // NumeroLote
        $numeroLote = $dom->createElement('NumeroLote', $dados['lote'] ?? '1');
        $loteRps->appendChild($numeroLote);
        
        // CnpjPrestador
        $cnpjPrestador = $dom->createElement('CnpjPrestador', $dados['prestador']['cnpj']);
        $loteRps->appendChild($cnpjPrestador);
        
        // InscricaoMunicipalPrestador
        if (!empty($dados['prestador']['inscricao_municipal'])) {
            $im = $dom->createElement('InscricaoMunicipalPrestador', $dados['prestador']['inscricao_municipal']);
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
     * 
     * @param DOMDocument $dom
     * @param array $dados
     * @return DOMElement
     */
    protected function criarRps(DOMDocument $dom, array $dados): DOMElement
    {
        // Rps
        $rps = $dom->createElement('Rps');
        
        // InfDeclaracaoPrestacaoServico
        $infDeclaracao = $dom->createElement('InfDeclaracaoPrestacaoServico');
        $infDeclaracao->setAttribute('Id', 'rps' . ($dados['numero'] ?? '1'));
        $rps->appendChild($infDeclaracao);
        
        // Rps > IdentificacaoRps
        $this->addIdentificacaoRps($dom, $infDeclaracao, $dados);
        
        // DataEmissao
        $dataEmissao = $dom->createElement(
            'DataEmissao',
            date('Y-m-d\TH:i:s', strtotime($dados['data_emissao'] ?? 'now'))
        );
        $infDeclaracao->appendChild($dataEmissao);
        
        // Status (1 = Normal)
        $status = $dom->createElement('Status', $dados['status'] ?? '1');
        $infDeclaracao->appendChild($status);
        
        // Competencia  
        $competencia = $dom->createElement(
            'Competencia',
            date('Y-m-d', strtotime($dados['competencia'] ?? 'now'))
        );
        $infDeclaracao->appendChild($competencia);
        
        // Serviço
        $this->addServico($dom, $infDeclaracao, $dados);
        
        // Prestador
        $this->addPrestador($dom, $infDeclaracao, $dados);
        
        // Tomador
        $this->addTomador($dom, $infDeclaracao, $dados);
        
        // Intermediário (se houver)
        if (!empty($dados['intermediario'])) {
            $this->addIntermediario($dom, $infDeclaracao, $dados);
        }
        
        // ConstrucaoCivil (se houver)
        if (!empty($dados['construcao_civil'])) {
            $this->addConstrucaoCivil($dom, $infDeclaracao, $dados);
        }
        
        return $rps;
    }
    
    /**
     * Adiciona identificação do RPS
     */
    protected function addIdentificacaoRps(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        $identificacao = $dom->createElement('Rps');
        
        $numero = $dom->createElement('Numero', $dados['numero'] ?? '1');
        $identificacao->appendChild($numero);
        
        $serie = $dom->createElement('Serie', $dados['serie'] ?? 'A');
        $identificacao->appendChild($serie);
        
        $tipo = $dom->createElement('Tipo', $dados['tipo'] ?? '1'); // 1=RPS, 2=Nota Conjugada, 3=Cupom
        $identificacao->appendChild($tipo);
        
        $parent->appendChild($identificacao);
    }
    
    /**
     * Adiciona informações do serviço
     */
    protected function addServico(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        $servico = $dom->createElement('Servico');
        
        // Valores
        $valores = $dom->createElement('Valores');
        
        $valorServicos = $dom->createElement('ValorServicos', number_format($dados['valor_servicos'], 2, '.', ''));
        $valores->appendChild($valorServicos);
        
        if (!empty($dados['valor_deducoes'])) {
            $valorDeducoes = $dom->createElement('ValorDeducoes', number_format($dados['valor_deducoes'], 2, '.', ''));
            $valores->appendChild($valorDeducoes);
        }
        
        if (!empty($dados['valor_pis'])) {
            $valorPis = $dom->createElement('ValorPis', number_format($dados['valor_pis'], 2, '.', ''));
            $valores->appendChild($valorPis);
        }
        
        if (!empty($dados['valor_cofins'])) {
            $valorCofins = $dom->createElement('ValorCofins', number_format($dados['valor_cofins'], 2, '.', ''));
            $valores->appendChild($valorCofins);
        }
        
        if (!empty($dados['valor_inss'])) {
            $valorInss = $dom->createElement('ValorInss', number_format($dados['valor_inss'], 2, '.', ''));
            $valores->appendChild($valorInss);
        }
        
        if (!empty($dados['valor_ir'])) {
            $valorIr = $dom->createElement('ValorIr', number_format($dados['valor_ir'], 2, '.', ''));
            $valores->appendChild($valorIr);
        }
        
        if (!empty($dados['valor_csll'])) {
            $valorCsll = $dom->createElement('ValorCsll', number_format($dados['valor_csll'], 2, '.', ''));
            $valores->appendChild($valorCsll);
        }
        
        // Outras Retenções  
        if (!empty($dados['outras_retencoes'])) {
            $outrasRetencoes = $dom->createElement('OutrasRetencoes', number_format($dados['outras_retencoes'], 2, '.', ''));
            $valores->appendChild($outrasRetencoes);
        }
        
        // Descontos
        if (!empty($dados['desconto_incondicionado'])) {
            $descontoIncondicionado = $dom->createElement('DescontoIncondicionado', number_format($dados['desconto_incondicionado'], 2, '.', ''));
            $valores->appendChild($descontoIncondicionado);
        }
        
        if (!empty($dados['desconto_condicionado'])) {
            $descontoCondicionado = $dom->createElement('DescontoCondicionado', number_format($dados['desconto_condicionado'], 2, '.', ''));
            $valores->appendChild($descontoCondicionado);
        }
        
        // ISS Retido (1=Sim, 2=Não)
        $issRetido = $dom->createElement('IssRetido', $dados['iss_retido'] ?? '2');
        $valores->appendChild($issRetido);
        
        // Valor ISS
        if (!empty($dados['valor_iss'])) {
            $valorIss = $dom->createElement('ValorIss', number_format($dados['valor_iss'], 2, '.', ''));
            $valores->appendChild($valorIss);
        }
        
        $servico->appendChild($valores);
        
        // Item Lista Serviço (código LC 116/2003)
        $itemListaServico = $dom->createElement('ItemListaServico', $dados['codigo_servico'] ?? '01.01');
        $servico->appendChild($itemListaServico);
        
        // Código CNAE
        if (!empty($dados['codigo_cnae'])) {
            $codigoCnae = $dom->createElement('CodigoCnae', $dados['codigo_cnae']);
            $servico->appendChild($codigoCnae);
        }
        
        // Código Tributação Município
        if (!empty($dados['codigo_tributacao_municipio'])) {
            $codTrib = $dom->createElement('CodigoTributacaoMunicipio', $dados['codigo_tributacao_municipio']);
            $servico->appendChild($codTrib);
        }
        
        // Discriminação
        $discriminacao = $dom->createElement('Discriminacao');
        $discriminacao->appendChild($dom->createCDATASection($dados['discriminacao'] ?? ''));
        $servico->appendChild($discriminacao);
        
        // Código Município
        $codigoMunicipio = $dom->createElement('CodigoMunicipio', $this->codigoMunicipio);
        $servico->appendChild($codigoMunicipio);
        
        // Alíquota
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
        
        $cnpj = $dom->createElement('CpfCnpj');
        $cnpjElement = $dom->createElement('Cnpj', $dados['prestador']['cnpj']);
        $cnpj->appendChild($cnpjElement);
        $prestador->appendChild($cnpj);
        
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
        
        // CPF/CNPJ
        if (!empty($dados['tomador']['cpf']) || !empty($dados['tomador']['cnpj'])) {
            $cpfCnpj = $dom->createElement('CpfCnpj');
            
            if (!empty($dados['tomador']['cnpj'])) {
                $cnpj = $dom->createElement('Cnpj', $dados['tomador']['cnpj']);
                $cpfCnpj->appendChild($cnpj);
            } else {
                $cpf = $dom->createElement('Cpf', $dados['tomador']['cpf']);
                $cpfCnpj->appendChild($cpf);
            }
            
            $tomador->appendChild($cpfCnpj);
        }
        
        // Inscrição Municipal
        if (!empty($dados['tomador']['inscricao_municipal'])) {
            $im = $dom->createElement('InscricaoMunicipal', $dados['tomador']['inscricao_municipal']);
            $tomador->appendChild($im);
        }
        
        // Razão Social
        if (!empty($dados['tomador']['razao_social'])) {
            $razao = $dom->createElement('RazaoSocial', substr($dados['tomador']['razao_social'], 0, 115));
            $tomador->appendChild($razao);
        }
        
        // Endereço
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
        
        // Contato
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
     * Adiciona intermediário (se houver)
     */
    protected function addIntermediario(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        $intermediario = $dom->createElement('IntermediarioServico');
        
        $razao = $dom->createElement('RazaoSocial', $dados['intermediario']['razao_social']);
        $intermediario->appendChild($razao);
        
        $cpfCnpj = $dom->createElement('CpfCnpj');
        if (!empty($dados['intermediario']['cnpj'])) {
            $cnpj = $dom->createElement('Cnpj', $dados['intermediario']['cnpj']);
            $cpfCnpj->appendChild($cnpj);
        } else {
            $cpf = $dom->createElement('Cpf', $dados['intermediario']['cpf']);
            $cpfCnpj->appendChild($cpf);
        }
        $intermediario->appendChild($cpfCnpj);
        
        if (!empty($dados['intermediario']['inscricao_municipal'])) {
            $im = $dom->createElement('InscricaoMunicipal', $dados['intermediario']['inscricao_municipal']);
            $intermediario->appendChild($im);
        }
        
        $parent->appendChild($intermediario);
    }
    
    /**
     * Adiciona construção civil (se aplicável)
     */
    protected function addConstrucaoCivil(DOMDocument $dom, DOMElement $parent, array $dados): void
    {
        $construcao = $dom->createElement('ConstrucaoCivil');
        
        if (!empty($dados['construcao_civil']['codigo_obra'])) {
            $codigoObra = $dom->createElement('CodigoObra', $dados['construcao_civil']['codigo_obra']);
            $construcao->appendChild($codigoObra);
        }
        
        if (!empty($dados['construcao_civil']['art'])) {
            $art = $dom->createElement('Art', $dados['construcao_civil']['art']);
            $construcao->appendChild($art);
        }
        
        $parent->appendChild($construcao);
    }
    
    /**
     * Valida dados básicos antes da geração do XML
     * 
     * @param array $dados
     * @throws ValidationException
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
        $erros = $dom->getElementsByTagName('MensagemRetorno');
        if ($erros->length > 0) {
            $mensagens = [];
            foreach ($erros as $erro) {
                $codigo = $erro->getElementsByTagName('Codigo')->item(0)?->nodeValue ?? '';
                $mensagem = $erro->getElementsByTagName('Mensagem')->item(0)?->nodeValue ?? '';
                $correcao = $erro->getElementsByTagName('Correcao')->item(0)?->nodeValue ?? '';
                
                $mensagens[] = "[$codigo] $mensagem" . ($correcao ? " - $correcao" : '');
            }
            
            $response['mensagem'] = implode('; ', $mensagens);
            return $response;
        }
        
        // Sucesso - extrair dados da NFSe
        $nfse = $dom->getElementsByTagName('CompNfse')->item(0);
        if ($nfse) {
            $response['sucesso'] = true;
            $response['mensagem'] = 'NFSe gerada com sucesso';
            
            $infNfse = $nfse->getElementsByTagName('InfNfse')->item(0);
            if ($infNfse) {
                $response['dados'] = [
                    'numero' => $infNfse->getElementsByTagName('Numero')->item(0)?->nodeValue ?? '',
                    'codigo_verificacao' => $infNfse->getElementsByTagName('CodigoVerificacao')->item(0)?->nodeValue ?? '',
                    'data_emissao' => $infNfse->getElementsByTagName('DataEmissao')->item(0)?->nodeValue ?? '',
                    'xml' => $dom->saveXML($nfse)
                ];
            }
        }
        
        return $response;
    }
    
    /**
     * Consulta NFSe por RPS
     * 
     * @param array $dados
     * @return array
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
     * 
     * @param array $dados
     * @return array
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
