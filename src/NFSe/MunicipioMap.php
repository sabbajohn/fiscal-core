<?php

namespace freeline\FiscalCore\NFSe;

class MunicipioMap
{
    /** @return array<string, array{provider: string, versao?: string}> */
    public static function default(): array
    {
        return [
            // Mapear por código IBGE do município
            // Exemplos ilustrativos (ajuste conforme necessidade real)
            // '3550308' => ['provider' => 'abrasf-v2-soap', 'versao' => '2.04'], // São Paulo/SP (ex.: padrão próprio)
            // '3304557' => ['provider' => 'abrasf-v2-soap', 'versao' => '2.04'], // Rio de Janeiro/RJ (ex.: Nota Carioca)
        ];
    }
}
