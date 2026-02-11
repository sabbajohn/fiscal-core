<?php

namespace freeline\FiscalCore\Services\NFSe;

use freeline\FiscalCore\Support\Cache\FileCacheStore;

class NacionalCatalogService
{
    private string $apiBaseUrl;
    private int $timeout;
    private FileCacheStore $cache;
    private int $ttl;
    private $httpClient;

    public function __construct(
        string $apiBaseUrl,
        int $timeout = 30,
        ?FileCacheStore $cache = null,
        int $ttl = 86400,
        ?callable $httpClient = null
    ) {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
        $this->timeout = $timeout;
        $this->cache = $cache ?? new FileCacheStore();
        $this->ttl = $ttl;
        $this->httpClient = $httpClient;
    }

    /**
     * @return array{data: array, metadata: array}
     */
    public function listarMunicipios(bool $forceRefresh = false): array
    {
        $cacheKey = 'municipios';
        return $this->fetchWithCache(
            $cacheKey,
            '/catalogos/municipios',
            $forceRefresh
        );
    }

    /**
     * @return array{data: array, metadata: array}
     */
    public function consultarAliquotasMunicipio(string $codigoMunicipio, bool $forceRefresh = false): array
    {
        if (!preg_match('/^\d{7}$/', $codigoMunicipio)) {
            throw new \InvalidArgumentException('Código do município deve conter 7 dígitos');
        }

        $cacheKey = "aliquotas:{$codigoMunicipio}";
        return $this->fetchWithCache(
            $cacheKey,
            "/catalogos/municipios/{$codigoMunicipio}/aliquotas",
            $forceRefresh
        );
    }

    /**
     * @return array{data: array, metadata: array}
     */
    private function fetchWithCache(string $cacheKey, string $path, bool $forceRefresh): array
    {
        $cached = $this->cache->get($cacheKey, $this->ttl);
        if (!$forceRefresh && $cached !== null && $cached['stale'] === false) {
            return [
                'data' => is_array($cached['value']) ? $cached['value'] : [],
                'metadata' => [
                    'source' => 'cache',
                    'stale' => false,
                    'cache_key' => $cacheKey,
                ],
            ];
        }

        try {
            $json = $this->requestJson($path);
            $data = $json['data'] ?? $json;
            if (!is_array($data)) {
                $data = [];
            }

            $this->cache->put($cacheKey, $data);

            return [
                'data' => $data,
                'metadata' => [
                    'source' => 'remote',
                    'stale' => false,
                    'cache_key' => $cacheKey,
                ],
            ];
        } catch (\Throwable $e) {
            if ($cached !== null) {
                return [
                    'data' => is_array($cached['value']) ? $cached['value'] : [],
                    'metadata' => [
                        'source' => 'cache',
                        'stale' => true,
                        'cache_key' => $cacheKey,
                        'fallback_error' => $e->getMessage(),
                    ],
                ];
            }

            throw new \RuntimeException("Falha ao obter catálogo nacional: {$e->getMessage()}", 0, $e);
        }
    }

    private function requestJson(string $path): array
    {
        if (is_callable($this->httpClient)) {
            $result = call_user_func($this->httpClient, $path);
            if (is_array($result)) {
                return $result;
            }
            throw new \RuntimeException('Cliente HTTP mock retornou payload inválido');
        }

        $url = $this->apiBaseUrl . $path;
        $headers = ["Accept: application/json"];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new \RuntimeException("Erro cURL: {$curlErr}");
            }

            if ($status >= 400) {
                throw new \RuntimeException("HTTP {$status} ao consultar {$url}");
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $this->timeout,
                    'header' => implode("\r\n", $headers),
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                throw new \RuntimeException("Falha HTTP ao consultar {$url}");
            }
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Resposta JSON inválida do catálogo nacional');
        }

        return $decoded;
    }
}
