<?php

namespace freeline\FiscalCore\NFSe\Providers;

use freeline\FiscalCore\Contracts\NFSeProviderInterface;
use freeline\FiscalCore\NFSe\Exceptions\DependencyMissingException;
use freeline\FiscalCore\NFSe\Exceptions\NFSeException;

class SpedNfseProvider implements NFSeProviderInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!class_exists(\Composer\InstalledVersions::class) ||
            !\Composer\InstalledVersions::isInstalled('nfephp-org/sped-nfse')) {
            throw new DependencyMissingException(
                'Pacote nfephp-org/sped-nfse não instalado. Instale com: composer require nfephp-org/sped-nfse'
            );
        }
    }

    public function emitir(array $dados): string
    {
        throw new NFSeException('SpedNfseProvider emitir() ainda não implementado.');
    }

    public function consultar(string $chave): string
    {
        throw new NFSeException('SpedNfseProvider consultar() ainda não implementado.');
    }

    public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool
    {
        throw new NFSeException('SpedNfseProvider cancelar() ainda não implementado.');
    }
}
