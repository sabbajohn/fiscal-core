<?php

namespace freeline\FiscalCore\NFSe\Config;

class NFSeConfig
{
    public function __construct(private array $data) {}

    public function all(): array { return $this->data; }
    public function provider(): ?string { return $this->data['provider'] ?? null; }
    public function versao(): ?string { return $this->data['versao'] ?? null; }
    public function ibge(): ?string { return $this->data['municipio']['ibge'] ?? null; }
    public function ambiente(): ?string { return $this->data['ambiente'] ?? null; }
    public function transportOptions(): array { return $this->data['transport'] ?? []; }
}
