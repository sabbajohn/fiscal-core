<?php
/**
 * Exemplo de Configuração Completa para NFSe Nacional
 * 
 * Este exemplo mostra como configurar corretamente o provider Nacional
 * para evitar erros RNG9999 relacionados a campos ausentes ou assinatura.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Freeline\FiscalCore\Support\CertificateManager;
use Freeline\FiscalCore\Support\ConfigManager;
use Freeline\FiscalCore\Facade\NFSeFacade;

// ============================================================================
// 1. CONFIGURAÇÃO DO CERTIFICADO DIGITAL (OBRIGATÓRIO)
// ============================================================================

$certificadoPath = '/caminho/para/certificado.pfx';
$senha = 'senha_certificado';

// Configurar certificado - DEVE SER FEITO ANTES DA EMISSÃO
CertificateManager::getInstance()->setCertificate($certificadoPath, $senha);

// ============================================================================
// 2. CONFIGURAÇÃO DO PROVIDER NACIONAL
// ============================================================================

$configProvider = [
    // Ambiente
    'ambiente' => 'homologacao', // ou 'producao'
    
    // Assinatura digital
    'signature_mode' => 'required', // 'required', 'optional' ou 'none'
    
    // Configurações específicas do DPS
    'dps_versao' => '1.00',
    'dps_root' => false, // false = wrapper NFSe (padrão correto)
    'dps_send_paliq' => true, // Enviar alíquota quando tribISSQN=1
    'dps_require_im' => false, // Se true, força IM='ISENTO' quando não informado
    
    // Identificação do aplicativo
    'ver_aplic' => 'invoiceflow-1.0', // Identificação da aplicação (max 20 chars)
];

ConfigManager::getInstance()->setProviderConfig('Nacional', $configProvider);

// ============================================================================
// 3. DADOS DO DPS (DECLARAÇÃO DE PRESTAÇÃO DE SERVIÇO)
// ============================================================================

$dadosDps = [
    // --- Identificação ---
    'tpAmb' => '2', // 1=Produção, 2=Homologação
    'tpEmit' => '1', // 1=Prestador, 2=Emissor Web, 3=Intermediário
    'serie' => '900', // Para tpEmit=1: 900 a 999
    'nDPS' => '1', // Número sequencial (recomendado: até 9 dígitos)
    'dCompet' => '2026-02-14', // Data de competência (YYYY-MM-DD)
    'dhEmi' => gmdate('Y-m-d\TH:i:s\Z'), // Data/hora emissão UTC
    
    // --- Prestador ---
    'prestador' => [
        'cnpj' => '83188342000104',
        'inscricaoMunicipal' => '12345678', // ⚠️ IMPORTANTE: Pode ser obrigatório!
        // Se não tiver IM e dps_require_im=true, usará 'ISENTO'
        'razaoSocial' => 'EMPRESA PRESTADORA LTDA',
        'codigoMunicipio' => '4209102', // Código IBGE (Joinville/SC)
        'opSimpNac' => '1', // 1=Optante Simples, 2=Não optante
        'regEspTrib' => '0', // 0=Sem regime especial, 1-6=Regimes específicos
    ],
    
    // --- Tomador ---
    'tomador' => [
        'documento' => '18452135000153', // CNPJ ou CPF
        'razaoSocial' => 'H2T COMERCIO DE PRODUTOS E EQUIPAMENTOS LTDA - ME',
        // Opcional: endereço completo do tomador
    ],
    
    // --- Serviço ---
    'servico' => [
        'cTribNac' => '010701', // Código serviço nacional (6 dígitos)
        'cLocPrestacao' => '4209102', // Local da prestação
        'descricao' => '2 (DOIS) USUARIOS DEMANDER.',
        'tribISSQN' => '1', // 1=Tributável, 2=Isento, 3=Não incide, 4=Imune
        'tpRetISSQN' => '1', // 1=Retido, 2=Não retido, 3=Retido substituto
        'aliquota' => 2.00, // Alíquota do ISS em percentual (quando tribISSQN=1)
        // base_calculo será igual a valor_servicos se não informado
    ],
    
    // --- Valores ---
    'valor_servicos' => 130.00, // Valor total dos serviços
    
    // ⚠️ Os valores abaixo são calculados automaticamente:
    // - vCalc: Base de cálculo do ISS
    // - vISSQN: Valor do ISS (vServ × aliquota / 100)
    // - vTotTribMun: Total de tributos municipais
    // - pTotTribMun: Percentual total de tributos municipais
];

// ============================================================================
// 4. EMISSÃO DA NFSe
// ============================================================================

try {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "          EMISSÃO NFSe NACIONAL - CONFIGURAÇÃO COMPLETA\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Verificar se certificado está configurado
    $cert = CertificateManager::getInstance()->getCertificate();
    if ($cert === null) {
        throw new \RuntimeException(
            "⚠️ ERRO: Certificado digital não configurado!\n" .
            "Configure o certificado usando CertificateManager::getInstance()->setCertificate()"
        );
    }
    echo "✓ Certificado digital configurado\n";
    
    // Emitir NFSe
    echo "✓ Validando dados...\n";
    $facade = new NFSeFacade();
    $resultado = $facade->emitir('Nacional', $dadosDps);
    
    echo "✓ XML gerado e assinado\n";
    echo "✓ Enviado para SEFIN\n\n";
    
    if ($resultado['sucesso']) {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "                    ✓ EMISSÃO REALIZADA!\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        if (isset($resultado['dados']['chave_acesso'])) {
            echo "Chave de Acesso: " . $resultado['dados']['chave_acesso'] . "\n";
        }
        if (isset($resultado['dados']['id_dps'])) {
            echo "ID DPS: " . $resultado['dados']['id_dps'] . "\n";
        }
        if (isset($resultado['dados']['xml_retorno'])) {
            echo "\nXML da NFSe emitida disponível em: \$resultado['dados']['xml_retorno']\n";
        }
    } else {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "                    ✗ ERRO NA EMISSÃO\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        echo "Mensagem: " . $resultado['mensagem'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "\n✗ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

// ============================================================================
// 5. CHECKLIST DE VALIDAÇÃO
// ============================================================================
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "               CHECKLIST DE VALIDAÇÃO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$checklist = [
    "Certificado digital configurado" => isset($cert) && $cert !== null,
    "CNPJ prestador válido (14 dígitos)" => strlen(preg_replace('/\D/', '', $dadosDps['prestador']['cnpj'])) === 14,
    "Inscrição Municipal informada" => !empty($dadosDps['prestador']['inscricaoMunicipal']),
    "Série válida para tpEmit=1 (900-999)" => (int)$dadosDps['serie'] >= 900 && (int)$dadosDps['serie'] <= 999,
    "nDPS dentro do limite (≤9 dígitos)" => strlen((string)$dadosDps['nDPS']) <= 9,
    "Alíquota informada (tribISSQN=1)" => !empty($dadosDps['servico']['aliquota']),
    "Valor serviços > 0" => $dadosDps['valor_servicos'] > 0,
];

foreach ($checklist as $item => $status) {
    echo ($status ? "✓" : "✗") . " $item\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n\n";

// ============================================================================
// 6. RESOLUÇÃO DE PROBLEMAS COMUNS
// ============================================================================
echo "RESOLUÇÃO DE PROBLEMAS:\n\n";

echo "❌ RNG9999 - Erro não catalogado:\n";
echo "   Causas comuns:\n";
echo "   1. Falta assinatura digital (configure o certificado)\n";
echo "   2. Campo IM ausente (informe inscricaoMunicipal)\n";
echo "   3. Série fora da faixa (900-999 para tpEmit=1)\n";
echo "   4. Ordem incorreta dos elementos XML\n";
echo "   5. Campos obrigatórios ausentes (vCalc, vISSQN)\n\n";

echo "❌ Certificado não configurado:\n";
echo "   Execute antes da emissão:\n";
echo "   CertificateManager::getInstance()->setCertificate('/path/to/cert.pfx', 'senha');\n\n";

echo "❌ Série inválida:\n";
echo "   - tpEmit=1: Série 900 a 999\n";
echo "   - tpEmit=2: Série 990 a 999\n";
echo "   - tpEmit=3: Série 1 a 999\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
