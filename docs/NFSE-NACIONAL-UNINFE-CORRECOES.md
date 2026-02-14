# Correções NFSe Nacional Baseadas no Exemplo UniNFe 1.01

## 📊 Comparação: Nosso XML vs UniNFe

| Característica | Antes | Depois (Corrigido) |
|----------------|-------|-------------------|
| **Estrutura** | NFSe > infNFSe > DPS | **DPS direto** ✅ |
| **Namespace** | Sem namespace | `xmlns="http://www.sped.fazenda.gov.br/nfse"` ✅ |
| **Versão** | 1.00 | **1.01** ✅ |
| **regApTribSN** | Ausente | **Condicional** (quando opSimpNac ≠ 1) ✅ |
| **cNBS** | Ausente | **Opcional** (servico.cNBS) ✅ |
| **Endereço Tomador** | Ausente | **Completo** (end > endNac > cMun/CEP) ✅ |
| **Email Tomador** | Ausente | **Opcional** (tomador.email) ✅ |
| **Assinatura** | Obrigatória | **Required por padrão** ✅ |

---

## ✅ Correções Implementadas

### 1. **Estrutura XML Corrigida (DPS Direto)**

**Problema**: Usávamos wrapper `<NFSe><infNFSe><DPS>...`  
**Solução**: Agora usa `<DPS>` diretamente como raiz

```php
// src/Providers/NFSe/NacionalProvider.php linha ~759
private function shouldWrapDpsInNfse(): bool
{
    $root = strtolower((string)($this->config['dps_root'] ?? 'dps')); // Mudou de 'nfse' para 'dps'
    return $root === 'nfse'; // Default: false
}
```

**Resultado**:
```xml
<!-- ANTES: -->
<NFSe versao="1.00">
  <infNFSe Id="...">
    <DPS versao="1.00">
      <infDPS>...</infDPS>
    </DPS>
  </infNFSe>
</NFSe>

<!-- DEPOIS: -->
<DPS versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
  <infDPS Id="...">
    ...
  </infDPS>
</DPS>
```

---

### 2. **Namespace Correto**

**Problema**: XML sem namespace  
**Solução**: Adicionado namespace SEFIN

```php
// linha ~477
} else {
    $dps = $dom->createElementNS('http://www.sped.fazenda.gov.br/nfse', 'DPS');
    $dps->setAttribute('versao', $versao);
    $dom->appendChild($dps);
}
```

---

### 3. **Versão Atualizada para 1.01**

```php
// linha ~456
$versao = (string)($this->config['dps_versao'] ?? '1.01'); // Mudou de '1.00'
```

---

### 4. **Campo `regApTribSN` Condicional**

**Quando incluir**: Somente se `opSimpNac ≠ 1`

```php
// linha ~520-528
$regTrib = $dom->createElement('regTrib');
$prest->appendChild($regTrib);
$opSimpNac = (string)($dados['prestador']['opSimpNac'] ?? '1');
$this->appendNodeNoNs($dom, $regTrib, 'opSimpNac', $opSimpNac);

if ($opSimpNac !== '1') {
    $regApTribSN = (string)($dados['prestador']['regApTribSN'] ?? '1');
    $this->appendNodeNoNs($dom, $regTrib, 'regApTribSN', $regApTribSN);
}
```

**Uso nos dados**:
```php
'prestador' => [
    'opSimpNac' => '3', // 3 = Simples Nacional - excesso receita
    'regApTribSN' => '1', // ⚠️ OBRIGATÓRIO quando opSimpNac ≠ 1
]
```

---

### 5. **Campo `cNBS` Opcional**

Código de Nomenclatura Brasileira de Serviços (9 dígitos)

```php
// linha ~569-572
$cNBS = trim((string)($dados['servico']['cNBS'] ?? ''));
if ($cNBS !== '') {
    $this->appendNodeNoNs($dom, $cServ, 'cNBS', $cNBS);
}
```

**Uso**:
```php
'servico' => [
    'cTribNac' => '010101',
    'cNBS' => '115022000', // Opcional: código NBS do serviço
]
```

---

### 6. **Endereço Completo do Tomador**

**Novo método**: `addEnderecoTomador()` (linha ~1449)

```php
private function addEnderecoTomador(\DOMDocument $dom, \DOMElement $toma, array $endereco): void
{
    $end = $dom->createElement('end');
    $toma->appendChild($end);
    
    // endNac > cMun + CEP
    $cMun = trim((string)($endereco['codigoMunicipio'] ?? $endereco['cMun'] ?? ''));
    $cep = $this->onlyDigits((string)($endereco['cep'] ?? $endereco['CEP'] ?? ''));
    
    if ($cMun !== '' && $cep !== '') {
        $endNac = $dom->createElement('endNac');
        $end->appendChild($endNac);
        $this->appendNodeNoNs($dom, $endNac, 'cMun', $cMun);
        $this->appendNodeNoNs($dom, $endNac, 'CEP', $cep);
    }
    
    // Campos opcionais
    $this->appendNodeNoNs($dom, $end, 'xLgr', $endereco['logradouro'] ?? '');
    $this->appendNodeNoNs($dom, $end, 'nro', $endereco['numero'] ?? '');
    $this->appendNodeNoNs($dom, $end, 'xCpl', $endereco['complemento'] ?? '');
    $this->appendNodeNoNs($dom, $end, 'xBairro', $endereco['bairro'] ?? '');
}
```

**Uso**:
```php
'tomador' => [
    'documento' => '12345678901234',
    'razaoSocial' => 'Empresa Tomadora LTDA',
    'email' => 'contato@empresa.com.br', // ✅ Novo
    'endereco' => [ // ✅ Novo
        'cMun' => '4128104',
        'cep' => '80000000',
        'logradouro' => 'Rua Exemplo',
        'numero' => '123',
        'complemento' => 'Sala 4',
        'bairro' => 'Centro',
    ],
]
```

---

### 7. **Email do Tomador**

```php
// linha ~546-549
$email = trim((string)($dados['tomador']['email'] ?? ''));
if ($email !== '') {
    $this->appendNodeNoNs($dom, $toma, 'email', $email);
}
```

---

## 🎯 XML Final Esperado (Conforme UniNFe 1.01)

```xml
<?xml version="1.0" encoding="utf-8"?>
<DPS versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
  <infDPS Id="DPS420910228318834200010400900000000000000001">
    <tpAmb>2</tpAmb>
    <dhEmi>2026-02-14T00:02:57Z</dhEmi>
    <verAplic>invoiceflow-1.0</verAplic>
    <serie>00900</serie>
    <nDPS>1</nDPS>
    <dCompet>2026-02-14</dCompet>
    <tpEmit>1</tpEmit>
    <cLocEmi>4209102</cLocEmi>
    
    <prest>
      <CNPJ>83188342000104</CNPJ>
      <IM>12345678</IM>
      <regTrib>
        <opSimpNac>1</opSimpNac>
        <!-- regApTribSN ausente quando opSimpNac=1 -->
        <regEspTrib>0</regEspTrib>
      </regTrib>
    </prest>
    
    <toma>
      <CNPJ>18452135000153</CNPJ>
      <xNome>H2T COMERCIO DE PRODUTOS E EQUIPAMENTOS LTDA - ME</xNome>
      <end>
        <endNac>
          <cMun>4209102</cMun>
          <CEP>89200000</CEP>
        </endNac>
        <xLgr>Rua Exemplo</xLgr>
        <nro>123</nro>
        <xBairro>Centro</xBairro>
      </end>
      <email>contato@empresa.com.br</email>
    </toma>
    
    <serv>
      <locPrest>
        <cLocPrestacao>4209102</cLocPrestacao>
      </locPrest>
      <cServ>
        <cTribNac>010701</cTribNac>
        <xDescServ>2 (DOIS) USUARIOS DEMANDER.</xDescServ>
        <cNBS>115022000</cNBS>
      </cServ>
    </serv>
    
    <valores>
      <vServPrest>
        <vServ>130.00</vServ>
      </vServPrest>
      <trib>
        <tribMun>
          <tribISSQN>1</tribISSQN>
          <tpRetISSQN>1</tpRetISSQN>
          <pAliq>2.00</pAliq>
          <vCalc>130.00</vCalc>
          <vISSQN>2.60</vISSQN>
        </tribMun>
        <totTrib>
          <vTotTrib>
            <vTotTribFed>0.00</vTotTribFed>
            <vTotTribEst>0.00</vTotTribEst>
            <vTotTribMun>2.60</vTotTribMun>
          </vTotTrib>
          <pTotTrib>
            <pTotTribFed>0.00</pTotTribFed>
            <pTotTribEst>0.00</pTotTribEst>
            <pTotTribMun>2.00</pTotTribMun>
          </pTotTrib>
        </totTrib>
      </trib>
    </valores>
    
    <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
      <!-- Assinatura digital aplicada automaticamente -->
    </Signature>
  </infDPS>
</DPS>
```

---

## 🚀 Como Testar com as Novas Correções

```php
use Freeline\FiscalCore\Support\CertificateManager;
use Freeline\FiscalCore\Facade\NFSeFacade;

// 1. Configurar certificado
CertificateManager::getInstance()->setCertificate('/path/to/cert.pfx', 'senha');

// 2. Dados completos
$dados = [
    'tpAmb' => '2',
    'tpEmit' => '1',
    'serie' => '900',
    'nDPS' => '1',
    'dCompet' => '2026-02-14',
    
    'prestador' => [
        'cnpj' => '83188342000104',
        'inscricaoMunicipal' => '12345678', // ⚠️ Obrigatório
        'codigoMunicipio' => '4209102',
        'opSimpNac' => '1', // 1=Simples Nacional normal
        'regEspTrib' => '0',
    ],
    
    'tomador' => [
        'documento' => '18452135000153',
        'razaoSocial' => 'H2T COMERCIO DE PRODUTOS...',
        'email' => 'contato@h2t.com.br', // ⚠️ Novo
        'endereco' => [ // ⚠️ Novo
            'cMun' => '4209102',
            'cep' => '89200000',
            'logradouro' => 'Rua Test',
            'numero' => '123',
            'bairro' => 'Centro',
        ],
    ],
    
    'servico' => [
        'cTribNac' => '010701',
        'cNBS' => '115022000', // ⚠️ Novo (opcional)
        'descricao' => '2 (DOIS) USUARIOS DEMANDER.',
        'cLocPrestacao' => '4209102',
        'tribISSQN' => '1',
        'tpRetISSQN' => '1',
        'aliquota' => 2.00,
    ],
    
    'valor_servicos' => 130.00,
];

// 3. Emitir
$facade = new NFSeFacade();
$resultado = $facade->emitir('Nacional', $dados);

if ($resultado['sucesso']) {
    echo "✅ NFSe emitida com sucesso!\n";
} else {
    echo "❌ Erro: " . $resultado['mensagem'] . "\n";
}
```

---

## 📋 Checklist Atualizado

- [x] ✅ Estrutura: DPS direto (sem wrapper NFSe)
- [x] ✅ Namespace: http://www.sped.fazenda.gov.br/nfse
- [x] ✅ Versão: 1.01
- [x] ✅ regApTribSN: Condicional (quando opSimpNac ≠ 1)
- [x] ✅ cNBS: Suportado (opcional)
- [x] ✅ Endereço tomador: Completo
- [x] ✅ Email tomador: Suportado
- [x] ✅ Inscrição Municipal: Com fallback 'ISENTO'
- [x] ✅ Assinatura digital: Obrigatória por padrão

---

## 🎯 Próximo Passo

**Teste a emissão novamente**. Se ainda ocorrer RNG9999, o problema pode ser:

1. **Certificado não configurado** → Verifique `CertificateManager`
2. **IM ausente** → Informe `prestador.inscricaoMunicipal`
3. **Ambiente específico de Joinville** → Pode precisar configuração adicional do município

**Plano B**: Se RNG9999 persistir, implementar provider específico para Joinville/SC usando BETHA (conforme Webservice.xml linha 10).
