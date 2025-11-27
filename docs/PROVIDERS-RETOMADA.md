# 🚀 Sistema de Providers NFSe - Guia de Retomada

## 📍 Onde você está agora

✅ **Estrutura base implementada:**
- Interface de providers
- Classe abstrata base
- Provider ABRASF v2 (genérico)
- Provider específico Joinville
- Registry para carregar providers
- Arquivo de configuração de municípios
- Exemplo funcional

## 📁 Arquivos Importantes

### Contratos (Interfaces)
```
src/Contracts/
├── NFSeProviderInterface.php          # Interface base (emitir, consultar, cancelar)
└── NFSeProviderConfigInterface.php    # Interface estendida (config auxiliares)
```

### Implementações
```
src/Providers/NFSe/
├── AbstractNFSeProvider.php           # ⭐ Classe base - IMPLEMENTE AQUI
├── AbrasfV2Provider.php               # ⭐ Provider genérico ABRASF - IMPLEMENTE montarXmlRps()
└── JoinvilleProvider.php              # Exemplo de provider específico
```

### Suporte
```
src/Support/
└── ProviderRegistry.php               # Registry para carregar providers via config
```

### Configuração
```
config/
└── nfse-municipios.json              # ⭐ ADICIONE NOVOS MUNICÍPIOS AQUI
```

### Exemplos
```
scripts/
└── exemplo-providers-nfse.php        # Exemplo completo de uso
```

---

## 🎯 Próximos Passos para Implementação

### 1. Implementar montagem de XML (PRIORIDADE ALTA)
**Arquivo:** `src/Providers/NFSe/AbrasfV2Provider.php`

```php
protected function montarXmlRps(array $dados): string
{
    // TODO: Implementar estrutura XML conforme ABRASF v2
    // Referência: Manual ABRASF v2.02/2.03
    
    $xml = new \DOMDocument('1.0', 'UTF-8');
    $rps = $xml->createElement('Rps');
    // ... montar estrutura completa
    
    return $xml->saveXML();
}
```

### 2. Implementar envio SOAP
**Arquivo:** `src/Providers/NFSe/AbstractNFSeProvider.php`

```php
public function emitir(array $dados): string
{
    $this->validarDados($dados);
    $xml = $this->montarXmlRps($dados);
    
    // TODO: Adicionar aqui:
    // 1. Assinar XML com certificado
    // 2. Criar cliente SOAP
    // 3. Enviar para webservice
    // 4. Retornar XML de resposta
    
    $soapClient = new \SoapClient($this->getWsdlUrl());
    $resposta = $soapClient->GerarNfse(['xmlEnvio' => $xml]);
    
    return $resposta;
}
```

### 3. Implementar parser de resposta
**Arquivo:** `src/Providers/NFSe/AbrasfV2Provider.php`

```php
protected function processarResposta(string $xmlResposta): array
{
    // TODO: Parser XML de resposta
    // Extrair: número NFSe, código verificação, erros, etc.
    
    $dom = new \DOMDocument();
    $dom->loadXML($xmlResposta);
    
    // Extrair dados...
    
    return [
        'sucesso' => true,
        'numero_nfse' => '...',
        'codigo_verificacao' => '...'
    ];
}
```

### 4. Adicionar assinatura digital
```php
// TODO: Integrar com CertificateManager
$certManager = CertificateManager::getInstance();
$xmlAssinado = $certManager->assinarXml($xml);
```

---

## 🏙️ Como Adicionar Novo Município

### Opção 1: Município usa ABRASF padrão

**Edite:** `config/nfse-municipios.json`

```json
{
  "nome_municipio": {
    "provider": "AbrasfV2Provider",
    "codigo_municipio": "1234567",
    "wsdl": "https://url-do-webservice",
    "wsdl_homologacao": "https://url-homologacao",
    "wsdl_producao": "https://url-producao",
    "aliquota_format": "decimal",
    "versao": "2.02",
    "ambiente": "homologacao"
  }
}
```

### Opção 2: Município tem particularidades

1. **Crie novo provider:**
```bash
# Copie JoinvilleProvider.php como template
cp src/Providers/NFSe/JoinvilleProvider.php \
   src/Providers/NFSe/SeuMunicipioProvider.php
```

2. **Edite o provider:**
```php
<?php
namespace freeline\FiscalCore\Providers\NFSe;

class SeuMunicipioProvider extends AbrasfV2Provider
{
    // Sobrescreva apenas métodos que diferem
    
    protected function montarXmlRps(array $dados): string
    {
        $xml = parent::montarXmlRps($dados);
        // Adicionar campos específicos
        return $xml;
    }
}
```

3. **Registre no config:**
```json
{
  "seu_municipio": {
    "provider": "SeuMunicipioProvider",
    // ... resto da config
  }
}
```

---

## 💡 Como Usar no Código

### Uso Básico
```php
use freeline\FiscalCore\Support\ProviderRegistry;

// Carregar provider
$registry = ProviderRegistry::getInstance();
$provider = $registry->get('curitiba');

// Emitir NFSe
$dados = [
    'prestador' => [ /* ... */ ],
    'tomador' => [ /* ... */ ],
    'servico' => [ /* ... */ ],
    'valor_servicos' => 1000.00
];

$xmlNFSe = $provider->emitir($dados);

// Consultar
$xmlConsulta = $provider->consultar('numero-nfse');

// Cancelar
$sucesso = $provider->cancelar('numero-nfse', 'Motivo do cancelamento');
```

### Integração com NFSeAdapter
```php
// TODO: Atualizar NFSeAdapter para usar ProviderRegistry
class NFSeAdapter {
    public function emitir(string $municipio, array $dados): string
    {
        $registry = ProviderRegistry::getInstance();
        $provider = $registry->get($municipio);
        return $provider->emitir($dados);
    }
}
```

---

## 🧪 Testar Implementação

```bash
# Rodar exemplo completo
php scripts/exemplo-providers-nfse.php

# Rodar testes (quando implementar)
vendor/bin/phpunit tests/Providers/NFSeProviderTest.php
```

---

## 📚 Referências Úteis

- **Manual ABRASF:** Estrutura XML padrão NFSe
- **Documentação municípios:** Verifique particularidades de cada prefeitura
- **NFePHP Sped-NFSe:** Pode servir de referência (mas não depende dele)

---

## ✅ Checklist de Implementação

- [ ] Implementar `montarXmlRps()` em `AbrasfV2Provider`
- [ ] Implementar `processarResposta()` em `AbrasfV2Provider`
- [ ] Adicionar cliente SOAP em `AbstractNFSeProvider::emitir()`
- [ ] Integrar assinatura digital com `CertificateManager`
- [ ] Implementar consulta e cancelamento
- [ ] Adicionar tratamento de erros
- [ ] Criar testes unitários
- [ ] Documentar DTOs de entrada/saída
- [ ] Adicionar mais municípios em `config/nfse-municipios.json`
- [ ] Integrar com `NFSeAdapter` existente

---

## 🤝 Arquitetura

```
ProviderRegistry (singleton)
    ↓ carrega config de
nfse-municipios.json
    ↓ instancia
AbstractNFSeProvider (base)
    ↓ herdam
AbrasfV2Provider | JoinvilleProvider | OutrosProviders
    ↓ implementam
NFSeProviderConfigInterface
```

---

## 🎯 Objetivo Final

Um sistema onde você:
1. Adiciona município em JSON (sem código)
2. Se padrão ABRASF, reutiliza provider existente
3. Se tem particularidades, cria provider específico herdando do ABRASF
4. **Zero duplicação de código**

---

**✨ Sistema está pronto para desenvolvimento! Comece por `AbrasfV2Provider::montarXmlRps()`**
