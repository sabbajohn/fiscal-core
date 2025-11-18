<?php

namespace freeline\FiscalCore\Contracts;

interface ProdutoInterface
{
    public function validarGTIN(string $codigo): bool;
}
