<?php

namespace freeline\FiscalCore\Facade;

use freeline\FiscalCore\Adapters\NFCeAdapter;
use freeline\FiscalCore\Adapters\ImpressaoAdapter;

class NFCeFacade
{
	public function __construct(
		private NFCeAdapter $nfce,
		private ImpressaoAdapter $impressao
	) {}

	public function emitir(array $dados): string
	{
		return $this->nfce->emitir($dados);
	}

	public function consultar(string $chave): string
	{
		return $this->nfce->consultar($chave);
	}

	public function cancelar(string $chave, string $motivo, string $protocolo): bool
	{
		return $this->nfce->cancelar($chave, $motivo, $protocolo);
	}

	public function gerarDanfce(string $xmlAutorizado): string
	{
		return $this->impressao->gerarDanfce($xmlAutorizado);
	}
}

