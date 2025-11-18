<?php

namespace freeline\FiscalCore\NFSe;

use freeline\FiscalCore\Contracts\NFSeProviderInterface;
use freeline\FiscalCore\NFSe\Config\NFSeConfig;
use freeline\FiscalCore\NFSe\Exceptions\ProviderNotFoundException;

class ProviderResolver
{
    private NFSeConfig $config;
    private ProviderRegistry $registry;

    public function __construct(array|NFSeConfig $config, ProviderRegistry $registry)
    {
        $this->config = $config instanceof NFSeConfig ? $config : new NFSeConfig($config);
        $this->registry = $registry;
    }

    public function resolve(): NFSeProviderInterface
    {
        $providerKey = $this->config->provider();

        if (!$providerKey) {
            $ibge = $this->config->ibge();
            $map = MunicipioMap::default();
            if ($ibge && isset($map[$ibge])) {
                $providerKey = $map[$ibge]['provider'];
                // merge versao sugerida do mapa se não vier no config
                $cfg = $this->config->all();
                $cfg['versao'] = $cfg['versao'] ?? ($map[$ibge]['versao'] ?? null);
                $this->config = new NFSeConfig($cfg);
            }
        }

        if (!$providerKey) {
            throw new ProviderNotFoundException('Não foi possível determinar o provider NFSe. Defina "provider" no config ou mapeie o município.');
        }

        return $this->registry->make($providerKey, $this->config->all());
    }
}
