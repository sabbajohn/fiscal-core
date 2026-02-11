<?php

namespace freeline\FiscalCore\Contracts;

/**
 * Capacidades opcionais para o provider NFSe Nacional.
 *
 * Providers legados municipais não precisam implementar esta interface.
 */
interface NFSeNacionalCapabilitiesInterface
{
    public function consultarPorRps(array $identificacaoRps): string;

    public function consultarLote(string $protocolo): string;

    public function substituir(string $nfseOriginal, array $dadosSubstituicao): string;

    public function baixarXml(string $chave): string;

    public function baixarDanfse(string $chave): string;

    /**
     * @return array{data: array, metadata?: array}
     */
    public function listarMunicipiosNacionais(bool $forceRefresh = false): array;

    /**
     * @return array{data: array, metadata?: array}
     */
    public function consultarAliquotasMunicipio(string $codigoMunicipio, bool $forceRefresh = false): array;
}
