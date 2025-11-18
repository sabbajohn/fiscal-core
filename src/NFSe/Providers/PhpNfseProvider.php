<?php

namespace freeline\FiscalCore\NFSe\Providers;

use freeline\FiscalCore\Contracts\NFSeProviderInterface;
use freeline\FiscalCore\NFSe\Exceptions\DependencyMissingException;
use freeline\FiscalCore\NFSe\Exceptions\NFSeException;

class PhpNfseProvider implements NFSeProviderInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!class_exists(\Composer\InstalledVersions::class) ||
            !\Composer\InstalledVersions::isInstalled('lucas-simoes/php-nfse')) {
            throw new DependencyMissingException(
                'Pacote lucas-simoes/php-nfse não instalado. Instale com: composer require lucas-simoes/php-nfse'
            );
        }
    }

    public function emitir(array $dados): string
    {
        throw new NFSeException('PhpNfseProvider emitir() ainda não implementado.');
    }

    public function consultar(string $chave): string
    {
        throw new NFSeException('PhpNfseProvider consultar() ainda não implementado.');
    }

    public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool
    {
        throw new NFSeException('PhpNfseProvider cancelar() ainda não implementado.');
    }
}
