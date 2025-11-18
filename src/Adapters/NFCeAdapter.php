<?php

namespace freeline\FiscalCore\Adapters;

use freeline\FiscalCore\Contracts\NotaFiscalInterface;
use NfePHP\NFe\Tools;

class NFCeAdapter implements NotaFiscalInterface
{
	private Tools $tools;

	public function __construct(Tools $tools)
	{
		$this->tools = $tools;
	}

	public function emitir(array $dados): string
	{
		// Para NFC-e (modelo 65), o Tools deve estar configurado com CSC/CSRT
		// e demais parâmetros no JSON de configuração.
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

