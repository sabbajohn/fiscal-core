<?php

namespace freeline\FiscalCore\NFSe\Transport;

use freeline\FiscalCore\NFSe\Exceptions\DependencyMissingException;

class SoapTransport
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function client(string $wsdl): \SoapClient
    {
        if (!class_exists(\SoapClient::class)) {
            throw new DependencyMissingException('ext-soap não está habilitada.');
        }

        $opts = $this->buildOptions();
        return new \SoapClient($wsdl, $opts);
    }

    private function buildOptions(): array
    {
        $defaults = [
            'trace' => 1,
            'exceptions' => true,
            'connection_timeout' => $this->options['timeout'] ?? 30,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];

        if (!empty($this->options['cert'])) {
            $cert = $this->options['cert'];
            if (!empty($cert['pfx']) && !empty($cert['pass'])) {
                $defaults['local_cert'] = $cert['pfx'];
                $defaults['passphrase'] = $cert['pass'];
            }
        }

        if (!empty($this->options['proxy'])) {
            $proxy = $this->options['proxy'];
            $defaults['proxy_host'] = $proxy['host'] ?? null;
            $defaults['proxy_port'] = $proxy['port'] ?? null;
            $defaults['proxy_login'] = $proxy['user'] ?? null;
            $defaults['proxy_password'] = $proxy['pass'] ?? null;
        }

        return $defaults;
    }
}
