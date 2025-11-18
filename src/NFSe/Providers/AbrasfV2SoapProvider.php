<?php

namespace freeline\FiscalCore\NFSe\Providers;

use freeline\FiscalCore\Contracts\NFSeProviderInterface;
use freeline\FiscalCore\NFSe\Transport\SoapTransport;

class AbrasfV2SoapProvider implements NFSeProviderInterface
{
    private array $config;
    private SoapTransport $transport;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->transport = new SoapTransport($config['transport'] ?? []);
    }

    public function emitir(array $dados): string
    {
        return '';
    }

    public function consultar(string $chave): string
    {
        return '';
    }

    public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool
    {
        return false;
    }
}
