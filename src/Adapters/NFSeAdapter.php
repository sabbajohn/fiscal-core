<?php

namespace freeline\FiscalCore\Adapters;

use freeline\FiscalCore\Contracts\NotaServicoInterface;
use freeline\FiscalCore\Contracts\NFSeProviderInterface;
use freeline\FiscalCore\NFSe\ProviderResolver;

class NFSeAdapter implements NotaServicoInterface
{
	private NFSeProviderInterface $provider;

	public function __construct(ProviderResolver $resolver)
	{
		$this->provider = $resolver->resolve();
	}

	public function emitir(array $dados): string
	{
		return $this->provider->emitir($dados);
	}

	public function consultar(string $chave): string
	{
		return $this->provider->consultar($chave);
	}

	public function cancelar(string $chave, string $motivo, string $protocolo): bool
	{
		return $this->provider->cancelar($chave, $motivo, $protocolo);
	}
}

