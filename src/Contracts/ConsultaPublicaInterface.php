<?php

namespace freeline\FiscalCore\Contracts;

interface ConsultaPublicaInterface
{
    public function consultarCEP(string $cep): array;
    public function consultarCNPJ(string $cnpj): array;
    public function consultarBanco(string $codigo): array;
    public function listarBancos(): array;
}
