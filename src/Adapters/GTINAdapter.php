<?php

namespace freeline\FiscalCore\Adapters;

use freeline\FiscalCore\Contracts\ProdutoInterface;

class GTINAdapter implements ProdutoInterface
{
	public function validarGTIN(string $codigo): bool
	{
		$codigo = trim($codigo);
		if ($codigo === '' || strtoupper($codigo) === 'SEM GTIN') {
			return true;
		}

		// Se a lib oficial estiver disponível, usa-a
		if (class_exists('NFePHP\\Gtin\\Gtin')) {
			try {
				/** @var object $checker */
				$checker = \NFePHP\Gtin\Gtin::check($codigo);
				if (method_exists($checker, 'isValid')) {
					return (bool) $checker->isValid();
				}
			} catch (\Throwable $e) {
				// fallback para algoritmo local
			}
		}

		// Fallback: validação local por dígito verificador (GTIN-8/12/13/14)
		if (!preg_match('/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $codigo)) {
			return false;
		}

		return self::checksumIsValid($codigo);
	}

	private static function checksumIsValid(string $gtin): bool
	{
		$digits = str_split($gtin);
		$checkDigit = (int) array_pop($digits);
		$digits = array_reverse($digits);
		$sum = 0;
		foreach ($digits as $i => $d) {
			$n = (int) $d;
			// pesos alternados 3 e 1 começando em 3 (a partir da direita)
			$sum += ($i % 2 === 0) ? ($n * 3) : $n;
		}
		$calc = (10 - ($sum % 10)) % 10;
		return $calc === $checkDigit;
	}
}

