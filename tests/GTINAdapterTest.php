<?php

use PHPUnit\Framework\TestCase;
use freeline\FiscalCore\Adapters\GTINAdapter;

class GTINAdapterTest extends TestCase
{
    public function test_sem_gtin_is_valid(): void
    {
        $gtin = new GTINAdapter();
        $this->assertTrue($gtin->validarGTIN('SEM GTIN'));
        $this->assertTrue($gtin->validarGTIN('sem gtin'));
    }

    public function test_gtin8_valid_and_invalid(): void
    {
        $gtin = new GTINAdapter();
        $this->assertTrue($gtin->validarGTIN('12345670')); // válido (dígito 0)
        $this->assertFalse($gtin->validarGTIN('12345671')); // inválido
    }

    public function test_invalid_lengths(): void
    {
        $gtin = new GTINAdapter();
        $this->assertFalse($gtin->validarGTIN('123'));
        $this->assertFalse($gtin->validarGTIN('abcdefghijkl'));
    }
}
