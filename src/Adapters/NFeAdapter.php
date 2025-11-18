<?php

namespace freeline\FiscalCore\Adapters;

use freeline\FiscalCore\Contracts\NotaFiscalInterface;
use NfePHP\NFe\Tools;

class NFeAdapter implements NotaFiscalInterface
{
    private Tools $tools;

    public function __construct(Tools $tools)
    {
        $this->tools = $tools;
    }

    public function emitir(array $dados): string
    {
        return $this->tools->sefazEnviaLote([$dados]);
    }

    public function consultar(string $chave): string
    {
        return $this->tools->sefazConsultaChave($chave);
    }

    public function cancelar(string $chave, string $motivo, string $protocolo): bool
    {
        return $this->tools->sefazCancela($chave, $motivo, $protocolo);
    }
}