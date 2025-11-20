<?php

namespace freeline\FiscalCore\Support;

use NFePHP\NFe\Tools as NFeTools;
use NFePHP\DA\NFe\Danfe as DanfeNFe;
use NFePHP\DA\NFe\Danfe as DanfeNFCe;

/**
 * Factory para criação de Tools NFePHP com configuração centralizada
 * 
 * Utiliza os singletons CertificateManager e ConfigManager
 * para fornecer instâncias pré-configuradas
 */
class ToolsFactory
{
    /**
     * Cria instância de Tools para NFe
     */
    public static function createNFeTools(): NFeTools
    {
        $certManager = CertificateManager::getInstance();
        $configManager = ConfigManager::getInstance();

        if (!$certManager->isLoaded()) {
            throw new \RuntimeException('Certificado digital não carregado. Use CertificateManager::getInstance()->loadFromFile()');
        }

        if (!$certManager->isValid()) {
            throw new \RuntimeException('Certificado digital expirado ou inválido');
        }

        $config = json_encode($configManager->getNFeConfig());
        $certificate = $certManager->getCertificate();

        return new NFeTools($config, $certificate);
    }

    /**
     * Cria instância de Tools para NFCe
     */
    public static function createNFCeTools(): NFeTools
    {
        // NFCe usa a mesma classe Tools da NFe
        return self::createNFeTools();
    }

    /**
     * Cria instância de DANFE para NFe
     */
    public static function createDanfeNFe(string $xml): DanfeNFe
    {
        return new DanfeNFe($xml);
    }

    /**
     * Cria instância de DANFCE para NFCe
     */
    public static function createDanfeNFCe(string $xml): DanfeNFCe
    {
        return new DanfeNFCe($xml);
    }

    /**
     * Verifica se o ambiente está configurado corretamente
     */
    public static function validateEnvironment(): array
    {
        $errors = [];
        $warnings = [];

        $certManager = CertificateManager::getInstance();
        $configManager = ConfigManager::getInstance();

        // Verifica certificado
        if (!$certManager->isLoaded()) {
            $errors[] = 'Certificado digital não carregado';
        } elseif (!$certManager->isValid()) {
            $errors[] = 'Certificado digital expirado';
        } else {
            $daysLeft = $certManager->getDaysUntilExpiration();
            if ($daysLeft !== null && $daysLeft < 30) {
                $warnings[] = "Certificado expira em {$daysLeft} dias";
            }
        }

        // Verifica configurações obrigatórias
        $requiredConfigs = ['uf', 'municipio_ibge'];
        foreach ($requiredConfigs as $config) {
            if (!$configManager->get($config)) {
                $errors[] = "Configuração obrigatória não definida: {$config}";
            }
        }

        // Verifica CSC para NFCe
        if ($configManager->isProduction() && !$configManager->get('csc')) {
            $warnings[] = 'CSC não configurado - necessário para NFCe em produção';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'certificate_info' => $certManager->getCertificateInfo(),
            'environment' => $configManager->isProduction() ? 'Produção' : 'Homologação'
        ];
    }

    /**
     * Configuração rápida para desenvolvimento/testes
     */
    public static function setupForDevelopment(array $config = []): void
    {
        $configManager = ConfigManager::getInstance();
        
        $defaultConfig = [
            'ambiente' => 2, // homologação
            'uf' => 'SP',
            'municipio_ibge' => '3550308',
            'versao_nfe' => '4.00',
            'serie_nfe' => '1',
            'serie_nfce' => '1',
            'csc' => 'GPB0JBWLUR6HWFTVEAS6RJ69GPCROFPBBB8G', // CSC de exemplo
            'csc_id' => '000001'
        ];

        $configManager->load(array_merge($defaultConfig, $config));
    }

    /**
     * Configuração rápida para produção
     */
    public static function setupForProduction(array $config = []): void
    {
        $configManager = ConfigManager::getInstance();
        
        $requiredConfigs = ['csc', 'csc_id', 'uf', 'municipio_ibge'];
        foreach ($requiredConfigs as $required) {
            if (!isset($config[$required])) {
                throw new \InvalidArgumentException("Configuração obrigatória para produção: {$required}");
            }
        }

        $productionConfig = array_merge([
            'ambiente' => 1, // produção
            'versao_nfe' => '4.00',
            'serie_nfe' => '1',
            'serie_nfce' => '1',
        ], $config);

        $configManager->load($productionConfig);
    }
}