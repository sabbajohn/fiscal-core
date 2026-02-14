<?php

namespace freeline\FiscalCore\Support;

class NFSeProviderResolver
{
    public const NATIONAL_KEY = 'nfse_nacional';

    public function resolveKey(?string $input): string
    {
        $normalized = $this->normalizeInput($input);

        return $normalized === '' ? self::NATIONAL_KEY : $normalized;
    }

    public function isMunicipioIgnored(?string $input): bool
    {
        $normalized = $this->normalizeInput($input);

        return $normalized !== '' && $normalized === self::NATIONAL_KEY
            && strtolower(trim((string) $input)) !== self::NATIONAL_KEY;
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
        $providerKey = $this->resolveKey($input);

        return [
            'provider_key' => $providerKey,
            'municipio_input' => $input,
            'municipio_ignored' => $ignored,
            'warnings' => $ignored ? [
                "Parâmetro 'municipio' foi ignorado e resolvido para '" . self::NATIONAL_KEY . "'.",
            ] : [],
        ];
    }

    private function normalizeInput(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        $trimmed = trim($input);
        if ($trimmed === '') {
            return '';
        }

        $lower = strtolower($trimmed);
        if ($lower === self::NATIONAL_KEY) {
            return self::NATIONAL_KEY;
        }

        if (ctype_digit($trimmed)) {
            return str_pad($trimmed, 7, '0', STR_PAD_LEFT);
        }

        return $lower;
    }
}
