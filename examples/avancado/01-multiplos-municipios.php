<?php

/**
 * EXEMPLO AVANÇADO: Múltiplos municípios NFSe
 * 
 * Como trabalhar com diferentes municípios simultaneamente
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use freeline\FiscalCore\Facade\NFSeFacade;
use freeline\FiscalCore\Facade\FiscalFacade;

echo "🏘️ EXEMPLO AVANÇADO: Múltiplos Municípios NFSe\n";
echo "==============================================\n\n";

// === LISTAR MUNICÍPIOS DISPONÍVEIS ===
echo "1️⃣ MUNICÍPIOS CONFIGURADOS\n";
echo "---------------------------\n";

$fiscal = new FiscalFacade();
$municipios = $fiscal->nfse()->listarMunicipios();

if ($municipios->isSuccess()) {
    $data = $municipios->getData();
    $municipiosValidos = array_filter($data['municipios'], function($m) {
        return !str_starts_with($m, '_'); // Remove templates e comentários
    });
    
    echo "✅ Municípios configurados: " . implode(', ', $municipiosValidos) . "\n";
    echo "📊 Total: " . count($municipiosValidos) . " municípios\n\n";
    
    // === TESTAR CADA MUNICÍPIO ===
    echo "2️⃣ VALIDAÇÃO POR MUNICÍPIO\n";
    echo "---------------------------\n";
    
    foreach ($municipiosValidos as $municipio) {
        $nfse = new NFSeFacade($municipio);
        $info = $nfse->getProviderInfo();
        
        if ($info->isSuccess()) {
            $data = $info->getData();
            $providerClass = basename($data['provider_class']);
            echo "✅ {$municipio}: {$providerClass}\n";
        } else {
            echo "❌ {$municipio}: " . $info->getError() . "\n";
        }
    }
    
} else {
    echo "❌ Erro: " . $municipios->getError() . "\n";
}

// === EXEMPLO DE USO PRÁTICO ===
echo "\n3️⃣ EXEMPLO PRÁTICO: Emissão Multi-Município\n";
echo "-------------------------------------------\n";

$dadosBasicos = [
    'prestador' => [
        'cnpj' => '11222333000181',
        'inscricao_municipal' => '123456'
    ],
    'tomador' => [
        'cnpj' => '99888777000161',
        'razao_social' => 'Cliente LTDA'
    ],
    'servico' => [
        'codigo' => '1.01',
        'descricao' => 'Consultoria em TI',
        'valor' => 1500.00
    ]
];

$municipiosParaTeste = ['curitiba', 'joinville'];

foreach ($municipiosParaTeste as $municipio) {
    echo "\n📋 Testando {$municipio}:\n";
    
    // Criar facade específico para o município
    $nfse = new NFSeFacade($municipio);
    
    // Validar configuração primeiro
    $validacao = $nfse->validarMunicipio();
    if ($validacao->isSuccess()) {
        echo "  ✅ Configuração: OK\n";
        
        // Tentar emitir (em ambiente de teste)
        $emissao = $nfse->emitir($dadosBasicos);
        if ($emissao->isSuccess()) {
            $data = $emissao->getData();
            echo "  ✅ Emissão: " . ($data['type'] ?? 'sucesso') . "\n";
        } else {
            echo "  ℹ️ Emissão: " . $emissao->getError() . "\n";
        }
        
    } else {
        echo "  ❌ Configuração: " . $validacao->getError() . "\n";
    }
}

// === GERENCIAMENTO DINÂMICO ===
echo "\n4️⃣ GERENCIAMENTO DINÂMICO\n";
echo "-------------------------\n";

class GerenciadorNFSe 
{
    private array $instances = [];
    
    public function getInstance(string $municipio): NFSeFacade
    {
        if (!isset($this->instances[$municipio])) {
            $this->instances[$municipio] = new NFSeFacade($municipio);
        }
        return $this->instances[$municipio];
    }
    
    public function emitirPorMunicipio(string $municipio, array $dados): array
    {
        $nfse = $this->getInstance($municipio);
        $resultado = $nfse->emitir($dados);
        
        return [
            'municipio' => $municipio,
            'sucesso' => $resultado->isSuccess(),
            'dados' => $resultado->getData(),
            'erro' => $resultado->isError() ? $resultado->getError() : null
        ];
    }
    
    public function getStatus(): array
    {
        $status = [];
        foreach ($this->instances as $municipio => $instance) {
            $info = $instance->getProviderInfo();
            $status[$municipio] = [
                'configurado' => $info->isSuccess(),
                'provider' => $info->isSuccess() ? 
                    basename($info->getData()['provider_class']) : 
                    'erro'
            ];
        }
        return $status;
    }
}

$gerenciador = new GerenciadorNFSe();

// Teste com múltiplos municípios
$resultados = [];
foreach (['curitiba', 'joinville'] as $municipio) {
    $resultados[] = $gerenciador->emitirPorMunicipio($municipio, $dadosBasicos);
}

echo "📊 Resultados consolidados:\n";
foreach ($resultados as $resultado) {
    $icon = $resultado['sucesso'] ? '✅' : '❌';
    $status = $resultado['sucesso'] ? 'OK' : $resultado['erro'];
    echo "  {$icon} {$resultado['municipio']}: {$status}\n";
}

echo "\n🎯 CENÁRIOS DE USO:\n";
echo "• Empresa com filiais em múltiplos municípios\n";
echo "• Software house atendendo diversos clientes\n";
echo "• Contabilidade gerenciando várias empresas\n";
echo "• Sistema SaaS multi-tenant\n";

echo "\n💡 VANTAGENS:\n";
echo "✅ Configuração independente por município\n";
echo "✅ Providers específicos para cada prefeitura\n";
echo "✅ Cache automático de instâncias\n";
echo "✅ Error handling isolado por município\n";
echo "✅ Facilita manutenção e atualizações\n";