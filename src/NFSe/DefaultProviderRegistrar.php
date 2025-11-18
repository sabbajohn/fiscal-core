<?php

namespace freeline\FiscalCore\NFSe;

use freeline\FiscalCore\NFSe\Providers\AbrasfV2SoapProvider;
use freeline\FiscalCore\NFSe\Providers\SpedNfseProvider;
use freeline\FiscalCore\NFSe\Providers\PhpNfseProvider;

class DefaultProviderRegistrar
{
    public static function registerDefaults(ProviderRegistry $registry): void
    {
        if (!$registry->has('abrasf-v2-soap')) {
            $registry->register('abrasf-v2-soap', fn(array $cfg) => new AbrasfV2SoapProvider($cfg));
        }

        if (!$registry->has('sped-nfse')) {
            $registry->register('sped-nfse', fn(array $cfg) => new SpedNfseProvider($cfg));
        }

        if (!$registry->has('php-nfse')) {
            $registry->register('php-nfse', fn(array $cfg) => new PhpNfseProvider($cfg));
        }
    }
}
