<?php

namespace freeline\FiscalCore\Providers\NFSe;

/**
 * Provider específico para Joinville/SC
 * 
 * Joinville usa ABRASF v2.01 mas com algumas particularidades:
 * - Formato de alíquota: percentual (2 em vez de 0.02)
 * - Campos extras no XML
 * - Validações específicas
 * 
 * Este é um exemplo de provider que herda do ABRASF mas adiciona
 * customizações específicas do município.
 */
class JoinvilleProvider extends AbrasfV2Provider
{
    /**
     * Sobrescreve montagem de XML para adicionar particularidades de Joinville
     */
    protected function montarXmlRps(array $dados): string
    {
        // TODO: Chamar parent e adicionar campos específicos de Joinville
        $xmlBase = parent::montarXmlRps($dados);
        
        // Adicionar campos extras de Joinville
        // - Campo X
        // - Campo Y
        
        return $xmlBase;
    }
    
    /**
     * Joinville usa alíquota em formato percentual
     */
    public function getAliquotaFormat(): string
    {
        return 'percentual';
    }
    
    /**
     * Código IBGE de Joinville/SC
     */
    public function getCodigoMunicipio(): string
    {
        return '4209102';
    }
}
