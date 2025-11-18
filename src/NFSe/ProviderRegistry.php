<?php

namespace freeline\FiscalCore\NFSe;

use freeline\FiscalCore\Contracts\NFSeProviderInterface;

class ProviderRegistry
{
    /** @var array<string, callable(array): NFSeProviderInterface> */
    private array $factories = [];

    public function register(string $key, callable $factory): void
    {
        $this->factories[$key] = $factory;
    }

    public function has(string $key): bool
    {
        return isset($this->factories[$key]);
    }

    public function make(string $key, array $config): NFSeProviderInterface
    {
        if (!isset($this->factories[$key])) {
            throw new \InvalidArgumentException("NFSe provider '{$key}' não registrado");
        }
        return ($this->factories[$key])($config);
    }
}
