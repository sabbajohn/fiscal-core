<?php

namespace freeline\FiscalCore\Providers\NFSe;

/**
 * Provider específico para Joinville/SC
 * 
 * Joinville usa padrão PUBLICA (schema v03)
 */
class JoinvilleProvider extends PublicaProvider
{
    /**
     * Código IBGE de Joinville/SC
     */
    public function getCodigoMunicipio(): string
    {
        return '4209102';
    }
}
