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
     * 
     * @param bool $requireCertificate Se true, exige certificado válido. Se false, cria certificado auto-assinado temporário.
     * @throws \RuntimeException Se certificado for obrigatório e não estiver carregado/válido
     */
    public static function createNFeTools(bool $requireCertificate = true): NFeTools
    {
        $certManager = CertificateManager::getInstance();
        $configManager = ConfigManager::getInstance();

        // Se certificado é obrigatório, valida
        if ($requireCertificate) {
            if (!$certManager->isLoaded()) {
                throw new \RuntimeException('Certificado digital não carregado. Use CertificateManager::getInstance()->loadFromFile()');
            }

            if (!$certManager->isValid()) {
                throw new \RuntimeException('Certificado digital expirado ou inválido');
            }
            
            $certificate = $certManager->getCertificate();
        } else {
            // Para operações que não exigem certificado (como status e consultas),
            // usa certificado carregado se disponível, ou cria um dummy se necessário
            if ($certManager->isLoaded()) {
                $certificate = $certManager->getCertificate();
            } else {
                // Cria um certificado auto-assinado temporário para operações que não exigem assinatura
                $certificate = self::createDummyCertificate();
            }
        }

        $config = json_encode($configManager->getNFeConfig());

        return new NFeTools($config, $certificate);
    }

    /**
     * Cria instância de Tools para NFCe
     * 
     * @param bool $requireCertificate Se true, exige certificado válido. Se false, permite criar Tools sem certificado.
     */
    public static function createNFCeTools(bool $requireCertificate = true): NFeTools
    {
        // NFCe usa a mesma classe Tools da NFe
        return self::createNFeTools($requireCertificate);
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
            'csc_id' => '000001',
        ];

        $configManager->load(array_merge($defaultConfig, $config));
    }

    /**
     * Cria um certificado auto-assinado temporário para operações que não exigem assinatura
     * Usado apenas para consultas e status que não requerem certificado real
     * 
     * @return \NFePHP\Common\Certificate
     * @throws \RuntimeException Se houver erro ao gerar o certificado
     */
    private static function createDummyCertificate(): \NFePHP\Common\Certificate
    {
        // Gera um certificado auto-assinado temporário válido por 1 dia
        // Este certificado é usado apenas para satisfazer a API do NFePHP
        // em operações que não requerem assinatura digital real
        
        $configPath = null;
        
        try {
            // Gera nome único e seguro para arquivo temporário
            $uniqueId = bin2hex(random_bytes(16));
            $configPath = sys_get_temp_dir() . '/fiscal_core_openssl_' . $uniqueId . '.cnf';
            
            // Cria arquivo de configuração temporário do OpenSSL
            $opensslConfig = <<<EOT
[ req ]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
x509_extensions = v3_ca

[ dn ]
C = BR
ST = SP
L = Sao Paulo
O = Fiscal Core Temp
OU = Development
CN = temp.fiscal-core.local
emailAddress = temp@fiscal-core.local

[ v3_ca ]
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always,issuer
basicConstraints = critical,CA:true
keyUsage = critical,digitalSignature,keyCertSign,cRLSign
EOT;
            
            if (file_put_contents($configPath, $opensslConfig) === false) {
                throw new \RuntimeException("Falha ao criar arquivo de configuração OpenSSL temporário");
            }
            
            // Gera chave privada
            $privkey = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            
            if ($privkey === false) {
                throw new \RuntimeException("Falha ao gerar chave privada: " . openssl_error_string());
            }
            
            // Gera CSR
            $csr = openssl_csr_new([
                'countryName' => 'BR',
                'stateOrProvinceName' => 'SP',
                'localityName' => 'Sao Paulo',
                'organizationName' => 'Fiscal Core Temp',
                'organizationalUnitName' => 'Development',
                'commonName' => 'temp.fiscal-core.local',
                'emailAddress' => 'temp@fiscal-core.local'
            ], $privkey, ['config' => $configPath]);
            
            if ($csr === false) {
                throw new \RuntimeException("Falha ao gerar CSR: " . openssl_error_string());
            }
            
            // Assina certificado
            $cert = openssl_csr_sign($csr, null, $privkey, 1, ['config' => $configPath]);
            
            if ($cert === false) {
                throw new \RuntimeException("Falha ao assinar certificado: " . openssl_error_string());
            }
            
            // Exporta certificado e chave privada
            $certout = '';
            if (openssl_x509_export($cert, $certout) === false) {
                throw new \RuntimeException("Falha ao exportar certificado: " . openssl_error_string());
            }
            
            $pkeyout = '';
            if (openssl_pkey_export($privkey, $pkeyout, null, ['config' => $configPath]) === false) {
                throw new \RuntimeException("Falha ao exportar chave privada: " . openssl_error_string());
            }
            
            // Gera senha aleatória para o PFX
            $pfxPassword = bin2hex(random_bytes(16));
            
            // Cria PFX
            $pfxData = '';
            if (openssl_pkcs12_export($certout, $pfxData, $pkeyout, $pfxPassword) === false) {
                throw new \RuntimeException("Falha ao criar PFX: " . openssl_error_string());
            }
            
            // Carrega no formato que NFePHP espera
            return \NFePHP\Common\Certificate::readPfx($pfxData, $pfxPassword);
            
        } finally {
            // Garante limpeza do arquivo temporário mesmo em caso de erro
            if ($configPath !== null && file_exists($configPath)) {
                @unlink($configPath);
            }
        }
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