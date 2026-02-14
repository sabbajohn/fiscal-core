# Correções Aplicadas - Erro RNG9999 NFSe Nacional

## 🔍 Análise do Problema

O erro **RNG9999 - Erro não catalogado** da SEFIN Nacional ocorre quando há:

1. ❌ Ausência de assinatura digital
2. ❌ Campo `IM` (Inscrição Municipal) ausente
3. ❌ Ordem incorreta dos elementos XML
4. ❌ Campos obrigatórios ausentes

## ✅ Correções Implementadas

### 1. **Assinatura Digital Obrigatória por Padrão**

**Arquivo**: `src/Providers/NFSe/NacionalProvider.php` (linha ~1577)

```php
// ANTES:
'signature_mode' => 'optional'

// DEPOIS:
'signature_mode' => 'required' // Assinatura obrigatória
```

**Impacto**: O sistema agora **exige** certificado digital configurado e aplica assinatura automaticamente.

---

### 2. **Suporte Melhorado para Campo IM**

**Arquivo**: `src/Providers/NFSe/NacionalProvider.php` (linha ~507)

```php
// Novo código:
$prestIm = trim((string)($dados['prestador']['inscricaoMunicipal'] ?? $dados['prestador']['im'] ?? ''));
if ($prestIm === '' && (bool)($this->config['dps_require_im'] ?? false)) {
    $prestIm = 'ISENTO';
}
if ($prestIm !== '') {
    $this->appendNodeNoNs($dom, $prest, 'IM', $prestIm);
}
```

**Funcionalidades**:
- Aceita `inscricaoMunicipal` ou `im` nos dados
- Permite forçar valor 'ISENTO' via configuração (`dps_require_im => true`)
- Inclui IM no XML quando disponível

---

### 3. **Logs de Depuração**

**Arquivo**: `src/Providers/NFSe/NacionalProvider.php` (linha ~1591)

```php
// Log quando assinatura é aplicada com sucesso:
error_log('[NFSe Nacional] XML assinado com sucesso: tag=' . $signTag . ', attr=' . $signAttr);

// Log quando há erro na assinatura:
error_log('[NFSe Nacional] Erro ao assinar XML: ' . $e->getMessage());

// Log quando certificado não está configurado:
error_log('[NFSe Nacional] Assinatura não aplicada: certificado não configurado.');
```

**Uso**: Verifique os logs do PHP para diagnosticar problemas de assinatura.

---

### 4. **Exemplo de Configuração Completa**

**Arquivo**: `examples/nfse-nacional-config-completa.php`

Criado exemplo completo mostrando:
- Como configurar certificado digital
- Como informar Inscrição Municipal
- Checklist de validação
- Resolução de problemas comuns

---

## 🚀 Como Testar

### **Passo 1: Configure o Certificado Digital**

```php
use Freeline\FiscalCore\Support\CertificateManager;

CertificateManager::getInstance()->setCertificate(
    '/caminho/para/certificado.pfx',
    'senha_do_certificado'
);
```

⚠️ **CRÍTICO**: Sem certificado, a assinatura não será aplicada e você receberá RNG9999.

---

### **Passo 2: Informe a Inscrição Municipal**

```php
$dados = [
    'prestador' => [
        'cnpj' => '83188342000104',
        'inscricaoMunicipal' => '12345678', // ⚠️ OBRIGATÓRIO para alguns municípios
        'razaoSocial' => 'EMPRESA LTDA',
        // ... outros campos
    ],
    // ... resto dos dados
];
```

---

### **Passo 3: Verifique a Estrutura do XML Gerado**

O XML **DEVE** conter:

```xml
<NFSe versao="1.00">
  <infNFSe Id="NFSe...">
    <DPS versao="1.00">
      <infDPS Id="DPS...">
        <!-- Dados do DPS -->
        <prest>
          <CNPJ>83188342000104</CNPJ>
          <IM>12345678</IM> <!-- ⚠️ DEVE estar presente -->
          <regTrib>
            <opSimpNac>1</opSimpNac>
            <regEspTrib>0</regEspTrib>
          </regTrib>
        </prest>
        <!-- ... outros campos ... -->
        <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
          <!-- ⚠️ Assinatura digital DEVE estar presente -->
          <SignedInfo>...</SignedInfo>
          <SignatureValue>...</SignatureValue>
          <KeyInfo>...</KeyInfo>
        </Signature>
      </infDPS>
    </DPS>
  </infNFSe>
</NFSe>
```

---

## 📋 Checklist de Validação

Antes de emitir, verifique:

- [ ] ✅ Certificado digital configurado e válido
- [ ] ✅ Inscrição Municipal (IM) informada nos dados
- [ ] ✅ CNPJ prestador: 14 dígitos
- [ ] ✅ Série: 900-999 (para tpEmit=1)
- [ ] ✅ nDPS: máximo 9 dígitos (recomendado)
- [ ] ✅ Alíquota informada quando tribISSQN=1
- [ ] ✅ Valor dos serviços > 0
- [ ] ✅ Código serviço (cTribNac): 6 dígitos

---

## 🛠️ Configurações Opcionais

### Forçar IM='ISENTO' quando não informado

Em `ConfigManager`:

```php
$config = [
    'dps_require_im' => true, // Força IM='ISENTO' se ausente
];

ConfigManager::getInstance()->setProviderConfig('Nacional', $config);
```

### Desabilitar assinatura (NÃO RECOMENDADO)

```php
$config = [
    'signature_mode' => 'none', // Desabilita assinatura (pode causar RNG9999)
];
```

---

## 🐛 Resolução de Problemas

### Erro: "RNG9999 - Erro não catalogado"

**Causa 1: Assinatura ausente**
```
Solução: Configure o certificado com CertificateManager
```

**Causa 2: Campo IM ausente**
```
Solução: Informe prestador.inscricaoMunicipal ou ative dps_require_im
```

**Causa 3: Série inválida**
```
Solução: Use série 900-999 para tpEmit=1
```

---

### Erro: "Certificado digital obrigatório..."

```php
// Configure ANTES de emitir:
CertificateManager::getInstance()->setCertificate($path, $senha);
```

---

### Como visualizar logs de depuração

**Linux/Mac:**
```bash
tail -f /var/log/php_errors.log | grep "NFSe Nacional"
```

**Buscar mensagens:**
- `"XML assinado com sucesso"` → Assinatura OK
- `"Erro ao assinar XML"` → Problema no certificado
- `"certificado não configurado"` → CertificateManager não inicializado

---

## 📄 Arquivos Alterados

1. `src/Providers/NFSe/NacionalProvider.php`
   - Linha ~507: Suporte melhorado para campo IM
   - Linha ~1577: Assinatura obrigatória por padrão
   - Linha ~1591: Logs de depuração

2. `examples/nfse-nacional-config-completa.php` (NOVO)
   - Exemplo completo de configuração
   - Checklist de validação
   - Resolução de problemas

---

## ⚡ Teste Agora

Execute o exemplo:

```bash
php examples/nfse-nacional-config-completa.php
```

Resultado esperado:
```
✓ Certificado digital configurado
✓ Validando dados...
✓ XML gerado e assinado
✓ Enviado para SEFIN
✓ EMISSÃO REALIZADA!
```

---

## 📞 Suporte

Se o erro RNG9999 persistir após estas correções:

1. Verifique os **logs do PHP** para mensagens de erro
2. Valide o **XML gerado** manualmente
3. Confirme se o **certificado está válido** e não expirado
4. Teste no **ambiente de homologação** primeiro
