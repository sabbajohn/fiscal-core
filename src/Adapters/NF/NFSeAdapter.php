<?php

namespace freeline\FiscalCore\Adapters\NF;

use freeline\FiscalCore\Contracts\NotaServicoInterface;
use freeline\FiscalCore\Contracts\NFSeProviderInterface;
use freeline\FiscalCore\Support\ProviderRegistry;

/**
 * Adapter moderno para NFSe usando o novo sistema de Providers
 * Integrado com ProviderRegistry para carregamento automático de municípios
 */
class NFSeAdapter implements NotaServicoInterface
{
    private NFSeProviderInterface $provider;
    private string $municipio;

    public function __construct(string $municipio)
    {
        $this->municipio = $municipio;
        $registry = ProviderRegistry::getInstance();
        $this->provider = $registry->get($municipio);
    }

    /**
     * Emite uma NFSe
     */
    public function emitir(array $dados): string
    {
        return $this->provider->emitir($dados);
    }

    /**
     * Consulta uma NFSe por chave/número
     */
    public function consultar(string $chave): string
    {
        return $this->provider->consultar($chave);
    }

    /**
     * Cancela uma NFSe
     */
    public function cancelar(string $chave, string $motivo, string $protocolo): bool
    {
        return $this->provider->cancelar($chave, $motivo, $protocolo);
    }

    /**
     * Retorna o município configurado
     */
    public function getMunicipio(): string
    {
        return $this->municipio;
    }

    /**
     * Retorna informações do provider atual
     */
    public function getProviderInfo(): array
    {
        return [
            'municipio' => $this->municipio,
            'provider_class' => get_class($this->provider),
            'has_config' => method_exists($this->provider, 'getConfig'),
        ];
    }
}

