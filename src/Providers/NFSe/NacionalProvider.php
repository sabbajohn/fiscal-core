<?php

namespace freeline\FiscalCore\Providers\NFSe;

use freeline\FiscalCore\Contracts\NFSeNacionalCapabilitiesInterface;
use freeline\FiscalCore\Services\NFSe\NacionalCatalogService;
use freeline\FiscalCore\Support\Cache\FileCacheStore;
use freeline\FiscalCore\Support\CertificateManager;
use NFePHP\Common\Signer;

class NacionalProvider extends AbstractNFSeProvider implements NFSeNacionalCapabilitiesInterface
{
    private NacionalCatalogService $catalogService;
    private $httpClient;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->httpClient = $config['http_client'] ?? null;

        $cacheDir = $config['cache_dir'] ?? null;
        $cacheTtl = (int) ($config['cache_ttl'] ?? 86400);

        $this->catalogService = new NacionalCatalogService(
            $this->getNationalApiBaseUrl(),
            $this->getTimeout(),
            new FileCacheStore($cacheDir),
            $cacheTtl,
            is_callable($this->httpClient)
                ? function (string $path) {
                    return call_user_func($this->httpClient, 'GET', $path, null, []);
                }
                : null
        );
    }

    public function emitir(array $dados): string
    {
        $this->validarDados($dados);
        $xml = $this->montarXmlRps($dados);
        $xml = $this->assinarXmlSeNecessario($xml);

        return $this->enviarOperacao('emitir', $xml);
    }

    public function consultar(string $chave): string
    {
        if ($chave === '') {
            throw new \InvalidArgumentException('Chave da NFSe é obrigatória');
        }

        $xml = $this->buildConsultaXml($chave);
        return $this->enviarOperacao('consultar', $xml);
    }

    public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool
    {
        if ($chave === '' || $motivo === '') {
            throw new \InvalidArgumentException('Chave e motivo são obrigatórios para cancelamento');
        }

        $xml = $this->buildCancelamentoXml($chave, $motivo, $protocolo);
        $response = $this->enviarOperacao('cancelar', $xml);
        $parsed = $this->processarResposta($response);

        return (bool) ($parsed['sucesso'] ?? false);
    }

    public function substituir(string $nfseOriginal, array $dadosSubstituicao): string
    {
        if ($nfseOriginal === '') {
            throw new \InvalidArgumentException('NFSe original é obrigatória para substituição');
        }

        $this->validarDados($dadosSubstituicao);
        $xml = $this->buildSubstituicaoXml($nfseOriginal, $dadosSubstituicao);

        return $this->enviarOperacao('substituir', $xml);
    }

    public function consultarPorRps(array $identificacaoRps): string
    {
        foreach (['numero', 'serie', 'tipo'] as $campo) {
            if (!isset($identificacaoRps[$campo])) {
                throw new \InvalidArgumentException("Identificação RPS inválida: campo {$campo} é obrigatório");
            }
        }

        $xml = $this->buildConsultaRpsXml($identificacaoRps);
        return $this->enviarOperacao('consultar_rps', $xml);
    }

    public function consultarLote(string $protocolo): string
    {
        if ($protocolo === '') {
            throw new \InvalidArgumentException('Protocolo do lote é obrigatório');
        }

        $xml = $this->buildConsultaLoteXml($protocolo);
        return $this->enviarOperacao('consultar_lote', $xml);
    }

    public function baixarXml(string $chave): string
    {
        if ($chave === '') {
            throw new \InvalidArgumentException('Chave é obrigatória');
        }

        $xml = $this->buildDownloadXmlPayload('xml', $chave);
        return $this->enviarOperacao('baixar_xml', $xml);
    }

    public function baixarDanfse(string $chave): string
    {
        if ($chave === '') {
            throw new \InvalidArgumentException('Chave é obrigatória');
        }

        $xml = $this->buildDownloadXmlPayload('danfse', $chave);
        return $this->enviarOperacao('baixar_danfse', $xml);
    }

    public function listarMunicipiosNacionais(bool $forceRefresh = false): array
    {
        return $this->catalogService->listarMunicipios($forceRefresh);
    }

    public function consultarAliquotasMunicipio(string $codigoMunicipio, bool $forceRefresh = false): array
    {
        return $this->catalogService->consultarAliquotasMunicipio($codigoMunicipio, $forceRefresh);
    }

    protected function montarXmlRps(array $dados): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $ns = $this->getIntegrationNamespace();

        $envio = $dom->createElementNS($ns, 'GerarNfseEnvio');
        $envio->setAttribute('versao', $this->getVersao());
        $dom->appendChild($envio);

        $rps = $this->appendNode($dom, $envio, 'Rps');
        $inf = $this->appendNode($dom, $rps, 'InfDeclaracaoPrestacaoServico');
        $inf->setAttribute('Id', (string) ($dados['id'] ?? ('RPS' . ($dados['rps_numero'] ?? '1'))));

        $rpsInterno = $this->appendNode($dom, $inf, 'Rps');
        $identificacao = $this->appendNode($dom, $rpsInterno, 'IdentificacaoRps');
        $this->appendNode($dom, $identificacao, 'Numero', (string) ($dados['rps_numero'] ?? '1'));
        $this->appendNode($dom, $identificacao, 'Serie', (string) ($dados['rps_serie'] ?? 'A1'));
        $this->appendNode($dom, $identificacao, 'Tipo', (string) ($dados['rps_tipo'] ?? '1'));
        $this->appendNode($dom, $rpsInterno, 'DataEmissao', (string) ($dados['data_emissao_rps'] ?? date('Y-m-d')));
        $this->appendNode($dom, $rpsInterno, 'Status', (string) ($dados['status_rps'] ?? '1'));

        $this->appendNode($dom, $inf, 'Competencia', (string) ($dados['competencia'] ?? date('Y-m')));
        $this->appendNode($dom, $inf, 'NaturezaOperacao', (string) ($dados['natureza_operacao'] ?? '1'));
        $this->appendNode($dom, $inf, 'OptanteSimplesNacional', (string) ($dados['optante_simples_nacional'] ?? '1'));
        $this->appendNode($dom, $inf, 'IncentivadorCultural', (string) ($dados['incentivador_cultural'] ?? '2'));

        $servico = $this->appendNode($dom, $inf, 'Servico');
        $valores = $this->appendNode($dom, $servico, 'Valores');
        $valorServicos = (float) $dados['valor_servicos'];
        $aliquota = (float) ($dados['servico']['aliquota'] ?? 0);
        $baseCalculo = (float) ($dados['servico']['base_calculo'] ?? $valorServicos);

        $this->appendNode($dom, $valores, 'ValorServicos', $this->formatDecimal($valorServicos, 5));
        $this->appendNode($dom, $valores, 'ValorDeducoes', $this->formatDecimal((float) ($dados['servico']['valor_deducoes'] ?? 0), 5));
        $this->appendNode($dom, $valores, 'ValorPis', $this->formatDecimal((float) ($dados['servico']['valor_pis'] ?? 0), 5));
        $this->appendNode($dom, $valores, 'ValorCofins', $this->formatDecimal((float) ($dados['servico']['valor_cofins'] ?? 0), 5));
        $this->appendNode($dom, $valores, 'ValorInss', $this->formatDecimal((float) ($dados['servico']['valor_inss'] ?? 0), 5));
        $this->appendNode($dom, $valores, 'ValorIr', $this->formatDecimal((float) ($dados['servico']['valor_ir'] ?? 0), 5));
        $this->appendNode($dom, $valores, 'ValorCsll', $this->formatDecimal((float) ($dados['servico']['valor_csll'] ?? 0), 5));
        $this->appendNode($dom, $valores, 'IssRetido', (string) ($dados['servico']['iss_retido'] ?? '2'));
        $this->appendNode($dom, $valores, 'BaseCalculo', $this->formatDecimal($baseCalculo, 5));
        $this->appendNode($dom, $valores, 'Aliquota', $this->formatDecimal((float) $this->formatarAliquota($aliquota), 5));
        $this->appendNode($dom, $valores, 'ValorLiquidoNfse', $this->formatDecimal((float) ($dados['servico']['valor_liquido_nfse'] ?? $valorServicos), 5));
        $this->appendNode($dom, $valores, 'DescontoIncondicionado', $this->formatDecimal((float) ($dados['servico']['desconto_incondicionado'] ?? 0), 5));
        $this->appendNode($dom, $valores, 'DescontoCondicionado', $this->formatDecimal((float) ($dados['servico']['desconto_condicionado'] ?? 0), 5));

        $this->appendNode($dom, $servico, 'ItemListaServico', (string) ($dados['servico']['item_lista_servico'] ?? $dados['servico']['codigo']));
        $this->appendNode($dom, $servico, 'Discriminacao', (string) ($dados['servico']['discriminacao'] ?? ''));
        $this->appendNode($dom, $servico, 'InformacoesComplementares', (string) ($dados['servico']['informacoes_complementares'] ?? ''));
        $this->appendNode($dom, $servico, 'CodigoMunicipio', (string) ($dados['servico']['codigo_municipio'] ?? $this->getCodigoMunicipio()));

        $prestador = $this->appendNode($dom, $inf, 'Prestador');
        $this->appendNode($dom, $prestador, 'Cnpj', $this->onlyDigits((string) ($dados['prestador']['cnpj'] ?? '')));
        $this->appendNode($dom, $prestador, 'InscricaoMunicipal', (string) ($dados['prestador']['inscricaoMunicipal'] ?? ''));

        $tomador = $this->appendNode($dom, $inf, 'Tomador');
        $identTomador = $this->appendNode($dom, $tomador, 'IdentificacaoTomador');
        $cpfCnpj = $this->appendNode($dom, $identTomador, 'CpfCnpj');
        $docTomador = $this->onlyDigits((string) ($dados['tomador']['documento'] ?? ''));
        if (strlen($docTomador) === 14) {
            $this->appendNode($dom, $cpfCnpj, 'Cnpj', $docTomador);
        } else {
            $this->appendNode($dom, $cpfCnpj, 'Cpf', $docTomador);
        }
        $this->appendNode($dom, $tomador, 'RazaoSocial', (string) ($dados['tomador']['razaoSocial'] ?? ''));

        if (!empty($dados['tomador']['email']) || !empty($dados['tomador']['telefone'])) {
            $contato = $this->appendNode($dom, $tomador, 'Contato');
            if (!empty($dados['tomador']['telefone'])) {
                $this->appendNode($dom, $contato, 'Telefone', $this->onlyDigits((string) $dados['tomador']['telefone']));
            }
            if (!empty($dados['tomador']['email'])) {
                $this->appendNode($dom, $contato, 'Email', (string) $dados['tomador']['email']);
            }
        }

        return $dom->saveXML() ?: '';
    }

    protected function processarResposta(string $xmlResposta): array
    {
        if ($xmlResposta === '') {
            return [
                'sucesso' => false,
                'mensagem' => 'Resposta vazia',
                'dados' => [],
            ];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($xmlResposta)) {
            $errors = libxml_get_errors();
            $message = $errors[0]->message ?? 'XML inválido';
            libxml_clear_errors();

            return [
                'sucesso' => false,
                'mensagem' => trim($message),
                'dados' => [],
            ];
        }
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $status = $this->getNodeValue($dom, ['Sucesso', 'sucesso', 'Status', 'cStat']);
        $mensagem = $this->getNodeValue($dom, ['Mensagem', 'mensagem', 'xMotivo']);
        $numeroNfse = $this->firstNodeValue($xpath, [
            "//*[local-name()='InfNfse']/*[local-name()='Numero']",
            "//*[local-name()='NumeroNfse']",
            "//*[local-name()='numeroNfse']",
        ]);
        $codigoVerificacao = $this->firstNodeValue($xpath, [
            "//*[local-name()='InfNfse']/*[local-name()='CodigoVerificacao']",
            "//*[local-name()='CodigoVerificacao']",
        ]);
        $linkVisualizacao = $this->firstNodeValue($xpath, [
            "//*[local-name()='LinkVisualizacaoNfse']",
        ]);
        $protocolo = $this->firstNodeValue($xpath, [
            "//*[local-name()='Protocolo']",
            "//*[local-name()='nProt']",
        ]);
        $mensagensRetorno = $xpath->query("//*[local-name()='MensagemRetorno']/*[local-name()='Mensagem']");
        $temMensagemRetorno = $mensagensRetorno && $mensagensRetorno->length > 0;
        $sucesso = $this->normalizeBool($status) || (!$temMensagemRetorno && $numeroNfse !== null);

        return [
            'sucesso' => $sucesso,
            'mensagem' => $mensagem ?? ($sucesso ? 'Processado com sucesso' : 'Retorno sem status explícito'),
            'dados' => [
                'numero_nfse' => $numeroNfse,
                'codigo_verificacao' => $codigoVerificacao,
                'protocolo' => $protocolo,
                'link_visualizacao' => $linkVisualizacao,
                'cstat' => $this->getNodeValue($dom, ['cStat']),
                'xmotivo' => $this->getNodeValue($dom, ['xMotivo']),
            ],
        ];
    }

    public function validarDados(array $dados): bool
    {
        parent::validarDados($dados);

        if (empty($dados['prestador']['cnpj']) || strlen($this->onlyDigits((string) $dados['prestador']['cnpj'])) !== 14) {
            throw new \InvalidArgumentException('CNPJ do prestador inválido');
        }

        if (empty($dados['servico']['codigo'])) {
            throw new \InvalidArgumentException('Código de serviço é obrigatório');
        }

        if (!isset($dados['valor_servicos']) || (float) $dados['valor_servicos'] <= 0) {
            throw new \InvalidArgumentException('Valor de serviços deve ser maior que zero');
        }

        if (empty($dados['tomador']['documento'])) {
            throw new \InvalidArgumentException('Documento do tomador é obrigatório');
        }

        $docTomador = $this->onlyDigits((string) $dados['tomador']['documento']);
        if (!in_array(strlen($docTomador), [11, 14], true)) {
            throw new \InvalidArgumentException('Documento do tomador deve ser CPF (11) ou CNPJ (14)');
        }

        if (empty($dados['tomador']['razaoSocial'])) {
            throw new \InvalidArgumentException('Razão Social do tomador é obrigatória');
        }

        return true;
    }

    private function enviarOperacao(string $operacao, string $xml): string
    {
        $path = $this->resolveOperationPath($operacao);
        $response = $this->requestHttp('POST', $path, $xml, [
            'Content-Type: application/xml',
            'Accept: application/xml',
        ]);

        if ($response === '') {
            throw new \RuntimeException("Resposta vazia na operação {$operacao}");
        }

        return $response;
    }

    private function resolveOperationPath(string $operacao): string
    {
        $defaultMap = [
            'emitir' => '/nfse/emitir',
            'consultar' => '/nfse/consultar',
            'cancelar' => '/nfse/cancelar',
            'substituir' => '/nfse/substituir',
            'consultar_rps' => '/nfse/consultar-rps',
            'consultar_lote' => '/nfse/consultar-lote',
            'baixar_xml' => '/nfse/download/xml',
            'baixar_danfse' => '/nfse/download/danfse',
        ];

        $configured = $this->config['endpoints'][$operacao] ?? null;
        $path = (string) ($configured ?? $defaultMap[$operacao] ?? '');
        if ($path === '') {
            throw new \RuntimeException("Endpoint da operação {$operacao} não configurado");
        }

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    private function requestHttp(string $method, string $path, ?string $body = null, array $headers = []): string
    {
        if (is_callable($this->httpClient)) {
            $result = call_user_func($this->httpClient, $method, $path, $body, $headers);
            if (!is_string($result)) {
                throw new \RuntimeException('Cliente HTTP mock retornou payload inválido');
            }

            return $result;
        }

        $url = rtrim($this->getNationalApiBaseUrl(), '/') . $path;
        $authHeaders = $this->buildAuthHeaders();
        $allHeaders = array_merge($headers, $authHeaders);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->getTimeout(),
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $allHeaders,
            ]);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new \RuntimeException("Erro cURL: {$curlErr}");
            }

            if ($status >= 400) {
                throw new \RuntimeException("HTTP {$status} na operação {$path}");
            }

            return (string) $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'timeout' => $this->getTimeout(),
                'header' => implode("\r\n", $allHeaders),
                'content' => $body ?? '',
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException("Falha HTTP na operação {$path}");
        }

        return (string) $response;
    }

    private function buildAuthHeaders(): array
    {
        $auth = $this->getAuthConfig();
        $headers = [];

        if (!empty($auth['token'])) {
            $headers[] = 'Authorization: Bearer ' . $auth['token'];
        }

        if (!empty($auth['api_key'])) {
            $headers[] = 'X-API-Key: ' . $auth['api_key'];
        }

        return $headers;
    }

    private function buildConsultaXml(string $chave): string
    {
        return $this->simpleEnvelope('ConsultarNfseExternoEnvio', [
            'ChaveNfse' => $chave,
        ]);
    }

    private function buildCancelamentoXml(string $chave, string $motivo, ?string $protocolo): string
    {
        $payload = [
            'ChaveNfse' => $chave,
            'Motivo' => $motivo,
        ];
        if (!empty($protocolo)) {
            $payload['Protocolo'] = $protocolo;
        }

        return $this->simpleEnvelope('CancelarNfseEnvio', $payload);
    }

    private function buildSubstituicaoXml(string $nfseOriginal, array $dadosSubstituicao): string
    {
        return $this->simpleEnvelope('SubstituirNfseEnvio', [
            'NfseOriginal' => $nfseOriginal,
            'NfseSubstituta' => $this->montarXmlRps($dadosSubstituicao),
        ]);
    }

    private function buildConsultaRpsXml(array $identificacaoRps): string
    {
        return $this->simpleEnvelope('ConsultarNfsePorRpsEnvio', [
            'Numero' => (string) $identificacaoRps['numero'],
            'Serie' => (string) $identificacaoRps['serie'],
            'Tipo' => (string) $identificacaoRps['tipo'],
        ]);
    }

    private function buildConsultaLoteXml(string $protocolo): string
    {
        return $this->simpleEnvelope('ConsultarLoteRpsEnvio', [
            'Protocolo' => $protocolo,
        ]);
    }

    private function buildDownloadXmlPayload(string $tipo, string $chave): string
    {
        return $this->simpleEnvelope('DownloadNfseEnvio', [
            'Tipo' => $tipo,
            'ChaveNfse' => $chave,
        ]);
    }

    /**
     * @param array<string,string> $payload
     */
    private function simpleEnvelope(string $rootName, array $payload): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS($this->getIntegrationNamespace(), $rootName);
        $root->setAttribute('versao', $this->getVersao());
        $dom->appendChild($root);

        foreach ($payload as $node => $value) {
            $this->appendNode($dom, $root, $node, $value);
        }

        return $dom->saveXML() ?: '';
    }

    private function getNodeValue(\DOMDocument $dom, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            $node = $dom->getElementsByTagName($name)->item(0);
            if ($node !== null && $node->nodeValue !== null) {
                return trim($node->nodeValue);
            }
        }

        return null;
    }

    private function normalizeBool(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = strtolower(trim($value));
        return in_array($normalized, ['1', '100', '150', 'true', 'sucesso', 'ok'], true);
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }

    private function getIntegrationNamespace(): string
    {
        return (string) ($this->config['xml_namespace'] ?? 'http://www.publica.inf.br/integracao_nfse');
    }

    private function appendNode(\DOMDocument $dom, \DOMElement $parent, string $name, ?string $value = null): \DOMElement
    {
        $node = $dom->createElementNS($this->getIntegrationNamespace(), $name);
        if ($value !== null) {
            $node->nodeValue = $value;
        }
        $parent->appendChild($node);
        return $node;
    }

    /**
     * @param array<int,string> $queries
     */
    private function firstNodeValue(\DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                $value = trim((string) $nodes->item(0)?->textContent);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function formatDecimal(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, '.', '');
    }

    private function assinarXmlSeNecessario(string $xml): string
    {
        $signatureMode = (string) ($this->config['signature_mode'] ?? 'optional');
        if ($signatureMode === 'none') {
            return $xml;
        }

        $certManager = CertificateManager::getInstance();
        $certificate = $certManager->getCertificate();
        if ($certificate === null) {
            if ($signatureMode === 'required') {
                throw new \RuntimeException('Certificado digital obrigatório para assinatura XML em homologação.');
            }
            return $xml;
        }

        try {
            return Signer::sign(
                $certificate,
                $xml,
                'InfDeclaracaoPrestacaoServico',
                'Id',
                OPENSSL_ALGO_SHA256
            );
        } catch (\Throwable $e) {
            if ($signatureMode === 'required') {
                throw new \RuntimeException('Falha ao assinar XML NFSe: ' . $e->getMessage(), 0, $e);
            }
            return $xml;
        }
    }
}
