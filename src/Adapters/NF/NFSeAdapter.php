<?php

namespace freeline\FiscalCore\Adapters\NF;

use freeline\FiscalCore\Contracts\NotaServicoInterface;
use freeline\FiscalCore\Contracts\NFSeNacionalCapabilitiesInterface;
use freeline\FiscalCore\Contracts\NFSeProviderConfigInterface;
use freeline\FiscalCore\Contracts\NFSeProviderInterface;
use freeline\FiscalCore\Support\NFSeProviderResolver;
use freeline\FiscalCore\Support\ProviderRegistry;

/**
 * Adapter moderno para NFSe usando o novo sistema de Providers
 * Integrado com ProviderRegistry para carregamento automático de municípios
 */
class NFSeAdapter implements NotaServicoInterface
{
    private NFSeProviderInterface $provider;
    private string $municipio;
    private string $providerKey;
    private array $compatMetadata;

    public function __construct(string $municipio, ?NFSeProviderInterface $provider = null)
    {
        $this->municipio = $municipio;
        $resolver = new NFSeProviderResolver();
        $this->providerKey = $resolver->resolveKey($municipio);
        $this->compatMetadata = $resolver->buildMetadata($municipio);

        if ($provider !== null) {
            $this->provider = $provider;
            return;
        }

        $registry = ProviderRegistry::getInstance();
        $this->provider = $registry->get($this->providerKey);
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
    public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool
    {
        return $this->provider->cancelar($chave, $motivo, $protocolo);
    }

    public function substituir(string $chave, array $dados): string
    {
        return $this->provider->substituir($chave, $dados);
    }

    public function consultarPorRps(array $identificacaoRps): string
    {
        return $this->requireNacionalCapabilities()->consultarPorRps($identificacaoRps);
    }

    public function consultarLote(string $protocolo): string
    {
        return $this->requireNacionalCapabilities()->consultarLote($protocolo);
    }

    public function baixarXml(string $chave): string
    {
        return $this->requireNacionalCapabilities()->baixarXml($chave);
    }

    public function baixarDanfse(string $chave): string
    {
        return $this->requireNacionalCapabilities()->baixarDanfse($chave);
    }

    public function listarMunicipiosNacionais(bool $forceRefresh = false): array
    {
        return $this->requireNacionalCapabilities()->listarMunicipiosNacionais($forceRefresh);
    }

    public function consultarAliquotasMunicipio(
        string $codigoMunicipio,
        ?string $codigoServico = null,
        ?string $competencia = null,
        bool $forceRefresh = false
    ): array
    {
        return $this->requireNacionalCapabilities()->consultarAliquotasMunicipio(
            $codigoMunicipio,
            $codigoServico,
            $competencia,
            $forceRefresh
        );
    }

    public function consultarConvenioMunicipio(string $codigoMunicipio, bool $forceRefresh = false): array
    {
        return $this->requireNacionalCapabilities()->consultarConvenioMunicipio($codigoMunicipio, $forceRefresh);
    }

    public function validarLayoutDps(array $payload, bool $checkCatalog = true): array
    {
        return $this->requireNacionalCapabilities()->validarLayoutDps($payload, $checkCatalog);
    }

    public function gerarXmlDpsPreview(array $payload): ?string
    {
        return $this->requireNacionalCapabilities()->gerarXmlDpsPreview($payload);
    }

    public function validarXmlDps(array $payload): array
    {
        return $this->requireNacionalCapabilities()->validarXmlDps($payload);
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
        $info = [
            'municipio' => $this->municipio,
            'provider_key' => $this->providerKey,
            'provider_class' => get_class($this->provider),
            'has_config' => $this->provider instanceof NFSeProviderConfigInterface,
            'supports_nacional' => $this->provider instanceof NFSeNacionalCapabilitiesInterface,
            'municipio_ignored' => $this->compatMetadata['municipio_ignored'] ?? false,
            'warnings' => $this->compatMetadata['warnings'] ?? [],
        ];

        if ($this->provider instanceof NFSeProviderConfigInterface) {
            $info = array_merge($info, [
                'codigo_municipio' => $this->provider->getCodigoMunicipio(),
                'versao' => $this->provider->getVersao(),
                'versao_schema' => $this->provider->getVersao(),
                'ambiente' => $this->provider->getAmbiente(),
                'wsdl_url' => $this->provider->getWsdlUrl(),
                'api_base_url' => $this->provider->getNationalApiBaseUrl(),
                'timeout' => $this->provider->getTimeout(),
            ]);
        }

        return $info;
    }

    private function requireNacionalCapabilities(): NFSeNacionalCapabilitiesInterface
    {
        if (!$this->provider instanceof NFSeNacionalCapabilitiesInterface) {
            throw new \RuntimeException(
                "Provider '{$this->municipio}' não suporta capacidades avançadas da NFSe Nacional"
            );
        }

        return $this->provider;
    }
}
