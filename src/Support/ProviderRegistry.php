<?php

namespace freeline\FiscalCore\Support;

use freeline\FiscalCore\Contracts\NFSeProviderConfigInterface;

/**
 * Registry para carregar Providers de NFSe baseado em configuração externa
 * 
 * Permite centralizar o carregamento de providers sem duplicar código
 * quando múltiplos municípios compartilham a mesma implementação.
 * 
 * Uso:
 * ```php
 * $registry = ProviderRegistry::getInstance();
 * $provider = $registry->get('curitiba');
 * $provider->emitir($dados);
 * ```
 */
class ProviderRegistry
{
    private static ?self $instance = null;
    private array $config = [];
    private array $providers = [];
    
    private function __construct()
    {
        $this->loadConfig();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Carrega configurações de providers de arquivo JSON
     */
    private function loadConfig(): void
    {
        $configFile = __DIR__ . '/../../config/nfse-municipios.json';
        
        if (file_exists($configFile)) {
            $json = file_get_contents($configFile);
            $this->config = json_decode($json, true) ?? [];
        }
    }
    
    /**
     * Obtém um provider configurado para o município
     * 
     * @param string $municipio Nome ou código IBGE do município
     * @return NFSeProviderConfigInterface
     * @throws \RuntimeException Se município não configurado
     */
    public function get(string $municipio): NFSeProviderConfigInterface
    {
        // Retornar provider em cache se já instanciado
        if (isset($this->providers[$municipio])) {
            return $this->providers[$municipio];
        }
        
        // Verificar se município está configurado
        if (!isset($this->config[$municipio])) {
            throw new \RuntimeException(
                "Município '{$municipio}' não configurado. " .
                "Adicione em config/nfse-municipios.json"
            );
        }
        
        $config = $this->config[$municipio];
        
        // Verificar se provider class está especificado
        if (!isset($config['provider'])) {
            throw new \RuntimeException(
                "Provider class não especificado para município '{$municipio}'"
            );
        }
        
        // Montar nome completo da classe
        $providerClass = $this->resolveProviderClass($config['provider']);
        
        // Verificar se classe existe
        if (!class_exists($providerClass)) {
            throw new \RuntimeException(
                "Provider class não encontrado: {$providerClass}"
            );
        }
        
        // Instanciar provider com configuração
        $provider = new $providerClass($config);
        
        // Cachear para reutilização
        $this->providers[$municipio] = $provider;
        
        return $provider;
    }
    
    /**
     * Resolve o nome completo da classe do provider
     * 
     * @param string $providerName Nome curto (ex: "AbrasfV2Provider")
     * @return string Nome completo com namespace
     */
    private function resolveProviderClass(string $providerName): string
    {
        // Se já tem namespace completo, retornar como está
        if (str_contains($providerName, '\\')) {
            return $providerName;
        }
        
        // Adicionar namespace padrão
        return "freeline\\FiscalCore\\Providers\\NFSe\\{$providerName}";
    }
    
    /**
     * Lista todos os municípios configurados
     * 
     * @return array
     */
    public function listMunicipios(): array
    {
        return array_keys($this->config);
    }
    
    /**
     * Adiciona ou atualiza configuração de um município em runtime
     * 
     * @param string $municipio
     * @param array $config
     */
    public function register(string $municipio, array $config): void
    {
        $this->config[$municipio] = $config;
        
        // Limpar cache se existir
        unset($this->providers[$municipio]);
    }
    
    /**
     * Recarrega configurações do arquivo
     */
    public function reload(): void
    {
        $this->config = [];
        $this->providers = [];
        $this->loadConfig();
    }
    
    // Prevenir clonagem
    private function __clone() {}
    
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
