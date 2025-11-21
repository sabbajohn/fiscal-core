<?php

namespace freeline\FiscalCore\Adapters;

use freeline\FiscalCore\Contracts\ConsultaPublicaInterface;
use BrasilApi\Client;

class BrasilAPIAdapter implements ConsultaPublicaInterface
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function consultarCEP(string $cep): array
    {
        try {
            $cepLimpo = preg_replace('/\D/', '', $cep);
            $response = $this->client->cep()->get($cepLimpo);
            return $this->normalizeResponse($response);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao consultar CEP na BrasilAPI: ' . $e->getMessage(), 0, $e);
        }
    }

    public function consultarCNPJ(string $cnpj): array
    {
        try {
            $cnpjLimpo = preg_replace('/\D/', '', $cnpj);
            $response = $this->client->cnpj()->get($cnpjLimpo);
            return $this->normalizeResponse($response);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao consultar CNPJ na BrasilAPI: ' . $e->getMessage(), 0, $e);
        }
    }

    public function consultarBanco(string $codigo): array
    {
        try {
            $response = $this->client->banks()->get((int) $codigo);
            return $this->normalizeResponse($response);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao consultar banco na BrasilAPI: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listarBancos(): array
    {
        try {
            $response = $this->client->banks()->getList();
            return $this->normalizeResponse($response);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao listar bancos na BrasilAPI: ' . $e->getMessage(), 0, $e);
        }
    }

    public function consultaNcm(string $ncm): array
    {
        try {
            $ncmLimpo = preg_replace('/\D/', '', $ncm);
            $response = $this->client->ncm()->get($ncmLimpo);
            return $this->normalizeResponse($response);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao consultar NCM na BrasilAPI: ' . $e->getMessage(), 0, $e);
        }
    }

    public function pesquisarNcm(string $descricao = ''): array
    {
        try {
            $response = $this->client->ncm()->search($descricao);
            return $this->normalizeResponse($response);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao pesquisar NCM na BrasilAPI: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listarNcms(): array
    {
        try {
            $response = $this->client->ncm()->getList();
            return $this->normalizeResponse($response);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao listar NCMs na BrasilAPI: ' . $e->getMessage(), 0, $e);
        }
    }

    private function normalizeResponse($response): array
    {
        if (is_array($response)) {
            return $response;
        }
        if (is_object($response)) {
            return json_decode(json_encode($response), true) ?? [];
        }
        return [];
    }
}
