<?php

/**
 * EXEMPLOS FISCAL-CORE - Guia de Uso Completo
 * 
 * Este arquivo demonstra todos os casos de uso principais da biblioteca
 * fiscal-core após instalação via composer.
 * 
 * Instalação:
 * composer require sabbajohn/fiscal-core
 * 
 * @author fiscal-core
 * @version 2.0
 */

require_once __DIR__ . '/../vendor/autoload.php';

use freeline\FiscalCore\Facade\FiscalFacade;
use freeline\FiscalCore\Facade\NFeFacade;
use freeline\FiscalCore\Facade\NFCeFacade;
use freeline\FiscalCore\Facade\NFSeFacade;
use freeline\FiscalCore\Facade\TributacaoFacade;
use freeline\FiscalCore\Facade\ImpressaoFacade;

// =====================================================
// 🎯 EXEMPLO 1: USO BÁSICO - INTERFACE UNIFICADA
// =====================================================

echo "🚀 FISCAL-CORE - Exemplos de Uso\n";
echo "================================\n\n";

// Instância principal - orquestra todos os facades
$fiscal = new FiscalFacade();

echo "1️⃣ INTERFACE UNIFICADA (Recomendado para a maioria dos casos)\n";
echo "--------------------------------------------------------------\n";

// Verificar status geral do sistema
$status = $fiscal->verificarStatus();
if ($status->isSuccess()) {
    echo "✅ Sistema inicializado com sucesso\n";
} else {
    echo "⚠️ Sistema com problemas: " . $status->getError() . "\n";
}

// Consultar NCM (exemplo prático)
echo "\n🔍 Exemplo: Consultando NCM...\n";
$ncm = $fiscal->consultarNCM('22071000');
if ($ncm->isSuccess()) {
    $data = $ncm->getData();
    echo "✅ NCM encontrado: " . ($data['descricao'] ?? 'N/A') . "\n";
} else {
    echo "❌ Erro: " . $ncm->getError() . "\n";
}

// =====================================================
// 🎯 EXEMPLO 2: NFSe - EMISSÃO POR MUNICÍPIO
// =====================================================

echo "\n2️⃣ NFSe - EMISSÃO POR MUNICÍPIO\n";
echo "-------------------------------\n";

// Listar municípios disponíveis
$municipios = $fiscal->nfse()->listarMunicipios();
if ($municipios->isSuccess()) {
    $data = $municipios->getData();
    echo "🏘️ Municípios disponíveis: " . implode(', ', array_filter($data['municipios'], function($m) {
        return !str_starts_with($m, '_'); // Remove comentários e templates
    })) . "\n";
}

// Emitir NFSe para Curitiba
echo "\n📋 Exemplo: Emitindo NFSe em Curitiba...\n";
$dadosNfse = [
    'prestador' => [
        'cnpj' => '11222333000181',
        'inscricao_municipal' => '123456'
    ],
    'tomador' => [
        'cnpj' => '99888777000161',
        'razao_social' => 'Empresa Tomadora LTDA'
    ],
    'servico' => [
        'codigo' => '1.01',
        'descricao' => 'Análise e desenvolvimento de sistemas',
        'valor' => 1000.00
    ]
];

$nfseResult = $fiscal->emitirNFSe($dadosNfse, 'curitiba');
if ($nfseResult->isSuccess()) {
    $data = $nfseResult->getData();
    echo "✅ NFSe emitida: " . ($data['type'] ?? 'sucesso') . "\n";
} else {
    echo "ℹ️ NFSe (demo): " . $nfseResult->getError() . "\n";
}

// =====================================================
// 🎯 EXEMPLO 3: TRIBUTAÇÃO - CÁLCULO DE IMPOSTOS
// =====================================================

echo "\n3️⃣ TRIBUTAÇÃO - CÁLCULO DE IMPOSTOS\n";
echo "-----------------------------------\n";

$produto = [
    'ncm' => '85171231',
    'valor' => 299.90,
    'descricao' => 'Smartphone',
    'uf' => 'SP'
];

// Validar produto primeiro
$validacao = $fiscal->tributacao()->validarProduto($produto);
if ($validacao->isSuccess()) {
    echo "✅ Produto validado para cálculo\n";
    
    // Tentar calcular impostos (requer configuração IBPT)
    $impostos = $fiscal->tributacao()->calcular($produto);
    if ($impostos->isSuccess()) {
        $data = $impostos->getData();
        echo "💰 Impostos calculados: R$ " . number_format($data['tributos_federais'] ?? 0, 2, ',', '.') . "\n";
    } else {
        echo "ℹ️ Cálculo IBPT requer configuração (IBPT_CNPJ, IBPT_TOKEN)\n";
    }
} else {
    echo "❌ Produto inválido: " . $validacao->getError() . "\n";
}

// =====================================================
// 🎯 EXEMPLO 4: IMPRESSÃO - GERAÇÃO DE PDFs
// =====================================================

echo "\n4️⃣ IMPRESSÃO - GERAÇÃO DE PDFs\n";
echo "------------------------------\n";

// XML de exemplo (simplificado para demonstração)
$xmlExemplo = '<?xml version="1.0" encoding="UTF-8"?>
<NFe xmlns="http://www.portalfiscal.inf.br/nfe">
    <infNFe Id="NFe35200714200166000166550010000000011000000014">
        <ide>
            <cUF>35</cUF>
            <cNF>000000001</cNF>
            <natOp>Venda</natOp>
            <mod>55</mod>
        </ide>
    </infNFe>
</NFe>';

// Validar XML primeiro
$validacaoXml = $fiscal->impressao()->validarXML($xmlExemplo, 'nfe');
if ($validacaoXml->isSuccess()) {
    echo "✅ XML válido para impressão\n";
    
    // Gerar DANFE (em produção você usaria um XML real autorizado)
    $danfe = $fiscal->impressao()->gerarDanfe($xmlExemplo);
    if ($danfe->isSuccess()) {
        $data = $danfe->getData();
        echo "🖨️ DANFE gerado: " . number_format($data['size'] / 1024, 1) . "KB\n";
    } else {
        echo "ℹ️ DANFE: " . $danfe->getError() . "\n";
    }
} else {
    echo "⚠️ XML simplificado para exemplo\n";
}

// =====================================================
// 🎯 EXEMPLO 5: USO AVANÇADO - FACADES ESPECÍFICOS
// =====================================================

echo "\n5️⃣ USO AVANÇADO - FACADES ESPECÍFICOS\n";
echo "------------------------------------\n";

// NFSe para município específico
$nfseJoinville = new NFSeFacade('joinville');
$infoProvider = $nfseJoinville->getProviderInfo();
if ($infoProvider->isSuccess()) {
    $data = $infoProvider->getData();
    echo "🏘️ Provider Joinville: " . $data['provider_class'] . "\n";
} else {
    echo "ℹ️ Joinville: " . $infoProvider->getError() . "\n";
}

// Tributação standalone
$tributacao = new TributacaoFacade();
$statusTrib = $tributacao->verificarStatus();
if ($statusTrib->isSuccess()) {
    $data = $statusTrib->getData();
    echo "💰 Tributação disponível: " . ($data['status'] ?? 'unknown') . "\n";
}

// Impressão standalone
$impressao = new ImpressaoFacade();
$statusImp = $impressao->verificarStatus();
if ($statusImp->isSuccess()) {
    echo "🖨️ Impressão disponível: PHP " . PHP_VERSION . "\n";
}

// =====================================================
// 🎯 EXEMPLO 6: TRATAMENTO DE ERROS ROBUSTO
// =====================================================

echo "\n6️⃣ TRATAMENTO DE ERROS ROBUSTO\n";
echo "------------------------------\n";

// Exemplo de município inexistente
$nfseInvalido = new NFSeFacade('municipio_inexistente');
$resultado = $nfseInvalido->emitir(['dados' => 'teste']);

if ($resultado->isError()) {
    echo "🚫 Erro capturado: " . $resultado->getErrorCode() . "\n";
    
    // Acessar sugestões específicas
    $metadata = $resultado->getMetadata();
    if (isset($metadata['suggestions'])) {
        echo "💡 Sugestões disponíveis: " . count($metadata['suggestions']) . " itens\n";
        echo "   • " . $metadata['suggestions'][0] . "\n";
    }
    
    // Acessar municípios alternativos
    if (isset($metadata['available_municipios'])) {
        $municipiosValidos = array_filter($metadata['available_municipios'], function($m) {
            return !str_starts_with($m, '_');
        });
        echo "🏘️ Use um destes: " . implode(', ', array_slice($municipiosValidos, 0, 3)) . "...\n";
    }
}

// =====================================================
// 📊 RESUMO E PRÓXIMOS PASSOS
// =====================================================

echo "\n📊 RESUMO DE FUNCIONALIDADES\n";
echo "============================\n";
echo "✅ Interface unificada via FiscalFacade\n";
echo "✅ Error handling robusto (sem crashes)\n";
echo "✅ Múltiplos municípios NFSe suportados\n";
echo "✅ Integração BrasilAPI (consultas públicas)\n";
echo "✅ Geração de DANFE/DANFCE/DACTE/DAMDFE\n";
echo "✅ Validação de dados de entrada\n";
echo "✅ Respostas padronizadas (FiscalResponse)\n";

echo "\n🔧 CONFIGURAÇÃO ADICIONAL\n";
echo "=========================\n";
echo "• Certificados A1/A3: Configure para NFe/NFCe\n";
echo "• IBPT: Configure IBPT_CNPJ e IBPT_TOKEN para tributação\n";
echo "• NFSe: Revise config/nfse-municipios.json para seus municípios\n";

echo "\n📚 DOCUMENTAÇÃO\n";
echo "===============\n";
echo "• README.md: Visão geral e instalação\n";
echo "• examples/: Mais exemplos específicos\n";
echo "• docs/: Documentação detalhada da arquitetura\n";

echo "\n🎉 Biblioteca pronta para uso em produção!\n";