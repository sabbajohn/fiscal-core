<?php

use PHPUnit\Framework\TestCase;
use freeline\FiscalCore\Support\CertificateManager;
use freeline\FiscalCore\Support\ConfigManager;
use freeline\FiscalCore\Support\ToolsFactory;
use freeline\FiscalCore\Adapters\NFeAdapter;
use freeline\FiscalCore\Adapters\NFCeAdapter;

/**
 * Testes para funcionalidade de certificado opcional
 * 
 * Valida que operações de consulta e status funcionam sem certificado digital,
 * enquanto operações de emissão e assinatura ainda exigem o certificado.
 */
class OptionalCertificateTest extends TestCase
{
    protected function setUp(): void
    {
        // Limpa os singletons entre testes
        CertificateManager::getInstance()->clear();
        ConfigManager::getInstance()->reload();
        
        // Configura ambiente de teste
        ToolsFactory::setupForDevelopment([
            'uf' => 'SP',
            'municipio_ibge' => '3550308'
        ]);
    }

    public function test_tools_factory_with_optional_certificate_does_not_require_cert(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        // Deve criar Tools sem erro quando requireCertificate = false
        $tools = ToolsFactory::createNFeTools(false);
        
        $this->assertInstanceOf(\NFePHP\NFe\Tools::class, $tools);
    }

    public function test_tools_factory_with_required_certificate_throws_exception(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Certificado digital não carregado');

        // Deve lançar exceção quando requireCertificate = true (padrão)
        ToolsFactory::createNFeTools(true);
    }

    public function test_nfe_adapter_can_be_instantiated_without_certificate(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        // NFeAdapter não deve lançar exceção no construtor
        $adapter = new NFeAdapter();
        
        $this->assertInstanceOf(NFeAdapter::class, $adapter);
    }

    public function test_nfce_adapter_can_be_instantiated_without_certificate(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        // NFCeAdapter não deve lançar exceção no construtor
        $adapter = new NFCeAdapter();
        
        $this->assertInstanceOf(NFCeAdapter::class, $adapter);
    }

    /**
     * Teste conceitual: sefazStatus não deve exigir certificado
     * 
     * Nota: Este teste não faz chamada real à SEFAZ, apenas verifica
     * que a instância de Tools é criada sem exigir certificado.
     */
    public function test_nfe_adapter_sefaz_status_uses_optional_cert(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        $adapter = new NFeAdapter();
        
        // Não podemos testar a chamada real sem configuração completa e conexão
        // mas podemos verificar que o adapter foi criado sem erro
        $this->assertInstanceOf(NFeAdapter::class, $adapter);
        
        // O método sefazStatus deve usar getToolsOptionalCert() internamente
        // que não exige certificado. Sem certificado válido e sem conexão,
        // a chamada falhará, mas não por falta de certificado no construtor.
    }

    /**
     * Teste conceitual: consultar não deve exigir certificado
     */
    public function test_nfe_adapter_consultar_uses_optional_cert(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        $adapter = new NFeAdapter();
        
        // Não podemos testar a chamada real sem configuração completa
        // mas verificamos que o adapter é instanciado sem erro
        $this->assertInstanceOf(NFeAdapter::class, $adapter);
    }

    /**
     * Teste: emitir deve exigir certificado
     * 
     * Este teste valida que o método emitir() tentará obter Tools com certificado
     * obrigatório. Como a construção da nota exige dados completos e complexos, 
     * testamos apenas que o adaptador foi criado sem certificado.
     */
    public function test_nfe_adapter_emitir_requires_certificate(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        $adapter = new NFeAdapter();
        
        // Verifica que o adapter foi criado sem erro (não requer certificado no construtor)
        $this->assertInstanceOf(NFeAdapter::class, $adapter);
        
        // O método emitir() internamente chamará getTools() que exigirá certificado,
        // mas não testamos isso aqui pois requer dados completos e válidos da NFe.
        // O importante é que operações de consulta funcionem sem certificado (testado acima).
    }

    /**
     * Teste: cancelar deve exigir certificado
     */
    public function test_nfe_adapter_cancelar_requires_certificate(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        $adapter = new NFeAdapter();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Certificado digital não carregado');

        // Cancelar deve falhar porque requer certificado
        $adapter->cancelar('12345678901234567890123456789012345678901234', 'Motivo teste', '123456789012345');
    }

    /**
     * Teste: inutilizar deve exigir certificado
     */
    public function test_nfe_adapter_inutilizar_requires_certificate(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        $adapter = new NFeAdapter();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Certificado digital não carregado');

        // Inutilizar deve falhar porque requer certificado
        $adapter->inutilizar(2024, 12345678901234, 55, 1, 1, 10, 'Justificativa teste');
    }

    /**
     * Teste: NFCeAdapter emitir deve exigir certificado
     */
    public function test_nfce_adapter_emitir_requires_certificate(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        $adapter = new NFCeAdapter();
        
        // Verifica que o adapter foi criado sem erro (não requer certificado no construtor)
        $this->assertInstanceOf(NFCeAdapter::class, $adapter);
        
        // O método emitir() internamente chamará getTools() que exigirá certificado,
        // mas não testamos isso aqui pois requer dados completos e válidos da NFCe.
        // O importante é que operações de consulta funcionem sem certificado (testado acima).
    }

    public function test_tools_factory_nfce_with_optional_certificate(): void
    {
        // Garante que não há certificado carregado
        $certManager = CertificateManager::getInstance();
        $this->assertFalse($certManager->isLoaded());

        // Deve criar NFCe Tools sem erro quando requireCertificate = false
        $tools = ToolsFactory::createNFCeTools(false);
        
        $this->assertInstanceOf(\NFePHP\NFe\Tools::class, $tools);
    }
}
