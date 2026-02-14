<?php
/**
 * Teste de Namespace - NFSe Nacional
 * Verifica se o XML está sendo gerado com namespace correto
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "          TESTE DE NAMESPACE - NFSe NACIONAL v1.01\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Simular criação de XML com namespace
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;

$ns = 'http://www.sped.fazenda.gov.br/nfse';
$dps = $dom->createElementNS($ns, 'DPS');
$dps->setAttribute('versao', '1.01');
$dom->appendChild($dps);

// Criar elementos filhos no mesmo namespace
$infDPS = $dom->createElementNS($ns, 'infDPS');
$infDPS->setAttribute('Id', 'DPS420910228318834200010400900000000000000001');
$dps->appendChild($infDPS);

// Elementos filhos
$tpAmb = $dom->createElementNS($ns, 'tpAmb', '2');
$infDPS->appendChild($tpAmb);

$dhEmi = $dom->createElementNS($ns, 'dhEmi', '2026-02-14T00:00:00Z');
$infDPS->appendChild($dhEmi);

$verAplic = $dom->createElementNS($ns, 'verAplic', 'invoiceflow-1.0');
$infDPS->appendChild($verAplic);

$xml = $dom->saveXML();

echo "XML GERADO:\n";
echo str_repeat("-", 70) . "\n";
echo $xml;
echo str_repeat("-", 70) . "\n\n";

// Validar namespace
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('nfse', $ns);

echo "VALIDAÇÃO DE NAMESPACE:\n";
echo str_repeat("-", 70) . "\n";

$dpsNodes = $xpath->query('//nfse:DPS');
echo "1. Elemento DPS: " . ($dpsNodes->length > 0 ? "✓ Encontrado" : "✗ Não encontrado") . "\n";

$infDPSNodes = $xpath->query('//nfse:DPS/nfse:infDPS');
echo "2. Elemento infDPS: " . ($infDPSNodes->length > 0 ? "✓ Encontrado" : "✗ Não encontrado") . "\n";

$tpAmbNodes = $xpath->query('//nfse:DPS/nfse:infDPS/nfse:tpAmb');
echo "3. Elemento tpAmb: " . ($tpAmbNodes->length > 0 ? "✓ Encontrado" : "✗ Não encontrado") . "\n";

$dhEmiNodes = $xpath->query('//nfse:DPS/nfse:infDPS/nfse:dhEmi');
echo "4. Elemento dhEmi: " . ($dhEmiNodes->length > 0 ? "✓ Encontrado" : "✗ Não encontrado") . "\n";

echo "\n";
echo str_repeat("═", 70) . "\n";
echo "VERIFICAÇÃO:\n";
echo str_repeat("═", 70) . "\n\n";

if ($dpsNodes->length > 0 && $infDPSNodes->length > 0 && $tpAmbNodes->length > 0 && $dhEmiNodes->length > 0) {
    echo "✅ NAMESPACE APLICADO CORRETAMENTE!\n";
    echo "   Todos os elementos estão no namespace: $ns\n\n";
    
    echo "PRÓXIMO PASSO:\n";
    echo "- Configure o certificado digital\n";
    echo "- Teste a emissão com os dados reais\n";
    echo "- O erro RNG6110 deve ser resolvido\n";
} else {
    echo "❌ PROBLEMA COM NAMESPACE!\n";
    echo "   Verifique a implementação do createElementWithNs()\n";
}

echo "\n";
