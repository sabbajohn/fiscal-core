<?php

namespace freeline\FiscalCore\Contracts;

/**
 * Interface estendida para Providers NFSe com métodos auxiliares
 * 
 * Adiciona métodos de configuração e validação específicos
 * para providers municipais baseados em configuração externa.
 */
interface NFSeProviderConfigInterface extends NFSeProviderInterface
{
    /**
     * Retorna a URL do webservice (WSDL)
     * 
     * @return string URL do WSDL
     */
    public function getWsdlUrl(): string;
    
    /**
     * Retorna a versão do layout suportado
     * 
     * @return string Versão (ex: "2.02", "2.01")
     */
    public function getVersao(): string;
    
    /**
     * Retorna o formato de alíquota esperado pelo município
     * 
     * @return string 'decimal' (0.02) ou 'percentual' (2)
     */
    public function getAliquotaFormat(): string;
    
    /**
     * Retorna o código IBGE do município
     * 
     * @return string Código IBGE (7 dígitos)
     */
    public function getCodigoMunicipio(): string;
    
    /**
     * Valida se os dados estão no formato correto para este provider
     * 
     * @param array $dados
     * @return bool
     * @throws \InvalidArgumentException Se dados inválidos
     */
    public function validarDados(array $dados): bool;
}
