<?php

namespace freeline\FiscalCore\Support;

class NFSeProviderResolver
{
    public const NATIONAL_KEY = 'nfse_nacional';

    public function resolveKey(?string $input): string
    {
        return self::NATIONAL_KEY;
    }

    public function isMunicipioIgnored(?string $input): bool
    {
        if ($input === null || $input === '') {
            return false;
        }

        return strtolower($input) !== self::NATIONAL_KEY;
    }

    /**
     * @return array{
     *   provider_key:string,
     *   municipio_input:?string,
     *   municipio_ignored:bool,
     *   warnings:array<int, string>
     * }
     */
    public function buildMetadata(?string $input): array
    {
        $ignored = $this->isMunicipioIgnored($input);

        return [
            'provider_key' => self::NATIONAL_KEY,
            'municipio_input' => $input,
            'municipio_ignored' => $ignored,
            'warnings' => $ignored ? [
                "Parâmetro 'municipio' está deprecado para NFSe e será removido em versão futura.",
                "Roteamento NFSe agora é sempre nacional via '" . self::NATIONAL_KEY . "'.",
            ] : [],
        ];
    }
}
