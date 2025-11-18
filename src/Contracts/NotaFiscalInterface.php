<?php

namespace freeline\FiscalCore\Contracts;

interface NotaFiscalInterface
{
    public function emitir(array $dados): string;
    public function consultar(string $chave): string;
    public function cancelar(string $chave, string $motivo, string $protocolo): bool;
}
