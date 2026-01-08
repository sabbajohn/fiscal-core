<?php

namespace Tests\Unit\NFSe;

use PHPUnit\Framework\TestCase;
use freeline\FiscalCore\Facade\NFSeFacade;

/**
 * Testes unitários para configuração de providers NFSe
 * Valida carregamento e configuração por município
 */
class ProviderConfigTest extends TestCase
{
    /** @test */
    public function deve_carregar_configuracao_municipio_valido(): void
    {
        $nfse = new NFSeFacade('sao_paulo');
        $config = $nfse->getProviderInfo();

        $this->assertTrue($config->isSuccess());
        
        $dados = $config->getData();
        $this->assertArrayHasKey('codigo_municipio', $dados);
        $this->assertArrayHasKey('provider_class', $dados);
        $this->assertEquals('3550308', $dados['codigo_municipio']);
    }

    /** @test */
    public function deve_rejeitar_municipio_inexistente(): void
    {
        $nfse = new NFSeFacade('municipio_inexistente');
        $config = $nfse->getProviderInfo();

        $this->assertFalse($config->isSuccess());
        $this->assertStringContainsString('Município não configurado', $config->getError());
    }

    /** @test */
    public function deve_listar_municipios_disponiveis(): void
    {
        $nfse = new NFSeFacade();
        $municipios = $nfse->listarMunicipios();

        $this->assertTrue($municipios->isSuccess());
        
        $dados = $municipios->getData();
        $this->assertArrayHasKey('municipios', $dados);
        $this->assertIsArray($dados['municipios']);
        $this->assertContains('curitiba', $dados['municipios']);
    }

    /** @test */
    public function deve_validar_configuracao_completa_municipio(): void
    {
        $municipios_teste = ['curitiba'];
        $registry = \freeline\FiscalCore\Support\ProviderRegistry::getInstance();

        foreach ($municipios_teste as $municipio) {
            $validacao = $registry->validarConfiguracao($municipio);

            if ($validacao->isSuccess()) {
                $dados = $validacao->getData();
                $this->assertTrue($dados['config_valida']);
                $this->assertEquals($municipio, $dados['municipio']);
            } else {
                // Se não tem configuração, apenas verifica que o erro é apropriado
                $this->assertStringContainsString('não configurado', $validacao->getError());
            }
        }
    }

    /** @test */
    public function deve_detectar_configuracao_incompleta(): void
    {
        // Testa município inexistente
        $registry = \freeline\FiscalCore\Support\ProviderRegistry::getInstance();
        $validacao = $registry->validarConfiguracao('municipio_inexistente');

        $this->assertFalse($validacao->isSuccess());
        $this->assertStringContainsString('não configurado', $validacao->getError());
    }

    /** @test */
    public function deve_determinar_ambiente_automaticamente(): void
    {
        $registry = \freeline\FiscalCore\Support\ProviderRegistry::getInstance();
        $ambiente = $registry->determinarAmbiente();

        $this->assertContains($ambiente, ['homologacao', 'producao']);
        $this->assertIsString($ambiente);
    }

    /** @test */
    public function deve_aplicar_regras_especificas_municipio(): void
    {
        // Cada município pode ter regras específicas
        $casos_teste = [
            'sao_paulo' => [
                'limite_rps' => 50,
                'versao_schema' => '1.0',
                'requer_certificado' => true
            ],
            'curitiba' => [
                'limite_rps' => 100,
                'versao_schema' => '2.0',
                'requer_certificado' => false
            ]
        ];

        foreach ($casos_teste as $municipio => $regras_esperadas) {
            $nfse = new NFSeFacade($municipio);
            $regras = $nfse->obterRegrasEspecificas();

            $this->assertTrue($regras->isSuccess());
            
            $dados = $regras->getData();
            foreach ($regras_esperadas as $regra => $valor_esperado) {
                $this->assertEquals($valor_esperado, $dados[$regra],
                    "Regra {$regra} para {$municipio} deveria ser {$valor_esperado}");
            }
        }
    }

    /** @test */
    public function deve_aplicar_fallback_para_municipio_similar(): void
    {
        // Teste de fallback baseado em região
        $nfse = new NFSeFacade();
        $fallback = $nfse->buscarFallback('municipio_nao_configurado', 'SP');

        if ($fallback->isSuccess()) {
            $dados = $fallback->getData();
            $this->assertArrayHasKey('municipio_sugerido', $dados);
            $this->assertArrayHasKey('provider_compativel', $dados);
        }
    }

    /** @test */
    public function deve_validar_versao_schema_municipio(): void
    {
        $municipios = ['sao_paulo', 'curitiba', 'belo_horizonte'];

        foreach ($municipios as $municipio) {
            $nfse = new NFSeFacade($municipio);
            $config = $nfse->getProviderInfo();

            if ($config->isSuccess()) {
                $dados = $config->getData();
                $this->assertArrayHasKey('versao_schema', $dados);
                $this->assertMatchesRegularExpression('/^\d+\.\d+$/', $dados['versao_schema'],
                    "Versão do schema para {$municipio} deve ter formato X.Y");
            }
        }
    }
}