# 🚀 FISCAL-CORE - Biblioteca PHP para Operações Fiscais

[![PHP Version](https://img.shields.io/badge/PHP-%5E8.0-blue)](https://php.net)
[![Composer](https://img.shields.io/badge/composer-ready-green)](https://getcomposer.org)
[![License](https://img.shields.io/badge/license-MIT-orange)](LICENSE)

> **Biblioteca robusta e modular para operações fiscais brasileiras**
>
> NFe, NFCe, NFSe, Consultas Públicas, Tributação IBPT e muito mais!

## 📋 Sumário

- [📦 Instalação](#-instalação-via-composer)
- [⚡ Início Rápido](#-início-rápido)  
- [🎯 Funcionalidades](#-funcionalidades-principais)
- [📚 Exemplos Práticos](#-exemplos-práticos)
- [⚙️ Configuração](#️-configuração-opcional)
- [🏗️ Arquitetura](#️-arquitetura)
- [📊 Casos de Uso](#-casos-de-uso)
- [🔧 Requisitos](#-requisitos-técnicos)
- [🚨 Troubleshooting](#-troubleshooting)
- [🗺️ Roadmap](#️-roadmap)

## 📦 Instalação via Composer

```bash
composer require fiscal/fiscal-core
```

**Desenvolvimento local:**

```json
{
  "repositories": [
    { "type": "path", "url": "../fiscal-core" }
  ]
}
  
  2) Instale a dependência:
  
  ```bash
  composer require freeline/fiscal-core:@dev
  ```

Desenvolvimento local

- Após clonar este repositório, instale dependências:
  
  ```bash
  composer install
  ```

- Execute a suíte de testes para validar o ambiente:
  
  ```php


## ⚡ Início Rápido

```php
<?php
require 'vendor/autoload.php';

use Fiscal\Facade\FiscalFacade;

// Interface unificada - Uma classe para tudo!
$fiscal = new FiscalFacade();

// Primeira consulta - sem configuração necessária
$resultado = $fiscal->consultar(['ncm' => '84715010']);

if ($resultado->sucesso) {
    echo "✅ Funcionou! Dados: " . json_encode($resultado->dados, JSON_PRETTY_PRINT);
} else {
    echo "❌ Erro: " . $resultado->erro;
}
```

## 🎯 Funcionalidades Principais

### 📋 **Consultas Públicas**

| Função | API | Status |
| -------- | ----- | -------- |
| **CEP** | ViaCEP + BrasilAPI | ✅ |
| **CNPJ** | ReceitaWS + BrasilAPI | ✅ |
| **Bancos** | BrasilAPI | ✅ |
| **NCM** | BrasilAPI | ✅ |

### 📄 **Documentos Fiscais**

| Documento | Status | Providers |
| ----------- | -------- | ----------- |
| **NFe** | ✅ Pronto | NFePHP |
| **NFCe** | ✅ Pronto | NFePHP |
| **NFSe** | ✅ Multi-município | 15+ cidades |

### 💰 **Tributação**

- **IBPT** - Cálculo automático de tributos
- **Múltiplos produtos** em lote
- **Cache** inteligente
- **Fallbacks** por estado/federal

## 📚 Exemplos Práticos

### 🎓 **Para Iniciantes** ([examples/basico/](examples/basico/))

```bash
# Primeira consulta (sem configuração)
php examples/basico/01-primeira-consulta.php

# Status do sistema
php examples/basico/02-status-sistema.php  

# Consultas públicas (CEP, CNPJ, Bancos)
php examples/basico/03-consultas-publicas.php
```

### 🏢 **Para Produção** ([examples/avancado/](examples/avancado/))

```bash
# Múltiplos municípios NFSe
php examples/avancado/01-multiplos-municipios.php

# Error handling robusto
php examples/avancado/02-error-handling.php
```

### 📖 **Guia Completo**

```bash
# Visão geral de todas as funcionalidades
php examples/GuiaCompletoDeUso.php
```

> 📚 **Veja todos os exemplos organizados em [examples/README.md](examples/README.md)**

## ⚙️ Configuração (Opcional)

### 🔐 **Certificados NFe/NFCe**

```bash
# Coloque seu certificado .pfx em:
certs/certificado.pfx

# Configure via environment ou código
export NFE_CERT_PATH="/caminho/para/certificado.pfx"
export NFE_CERT_PASS="senha_do_certificado"
```

### 💰 **IBPT (Tributação)**

```bash
export IBPT_CNPJ="11222333000181"
export IBPT_TOKEN="seu_token_ibpt"  
export IBPT_UF="SP"
```

### 🏘️ **NFSe Municípios**

```json
// config/nfse-municipios.json
{
    "sao_paulo": {
        "codigo": "3550308",
        "provider": "SaoPauloProvider",
        "ambiente": "homologacao"
    }
}
```

## Uso Detalhado

### 1) **NFe: emitir, consultar e cancelar**

```php
use Fiscal\Facade\NFeFacade;

$nfe = new NFeFacade();

// Emissão
$resultado = $nfe->emitir($dadosNfe);
if ($resultado->sucesso) {
    echo "NFe emitida: " . $resultado->dados['chave'];
}

// Consulta por chave
$consulta = $nfe->consultar('43210315123456789012345678901234567890123456');
if ($consulta->sucesso) {
    echo "Status: " . $consulta->dados['status'];
}
```

### 2) **Impressão: DANFE/DANFCE**

```php
use Fiscal\Facade\ImpressaoFacade;

$impressao = new ImpressaoFacade();

// Gerar DANFE a partir do XML
$danfePdf = $impressao->gerarDanfe($xmlNfe);
file_put_contents('danfe.pdf', $danfePdf->dados);
```

### 3) **NFSe: múltiplos municípios**

```php
use Fiscal\Facade\NFSeFacade;

$nfse = new NFSeFacade();

// Emitir NFSe para São Paulo
$resultado = $nfse->emitir('sao_paulo', $dadosServico);
if ($resultado->sucesso) {
    echo "NFSe emitida: " . $resultado->dados['numero'];
}

// Consultar NFSe
$consulta = $nfse->consultar('sao_paulo', ['numero' => '123']);
```

### 4) **Consultas Públicas**

```php
use Fiscal\Facade\FiscalFacade;

$fiscal = new FiscalFacade();

// CEP
$cep = $fiscal->consultarCEP('01310-100');

// CNPJ  
$cnpj = $fiscal->consultarCNPJ('11222333000181');

// NCM
$ncm = $fiscal->consultarNCM('84715010');
```

## 🏗️ Arquitetura

### 🎭 **Sistema de Facades**

```bash
FiscalFacade (Interface Unificada)
├── NFeFacade (Documentos NFe)
├── NFCeFacade (NFCe/Cupons)  
├── NFSeFacade (Notas de Serviço)
├── TributacaoFacade (Cálculos IBPT)
└── ImpressaoFacade (DANFE/DANFSE)
```

### 🔄 **Sistema de Respostas**

```php
FiscalResponse {
    bool $sucesso;      // true/false
    mixed $dados;       // dados retornados
    string $erro;       // mensagem de erro
    array $detalhes;    // informações extras
}
```

### 🛡️ **Error Handling**

- **Fallbacks** automáticos entre providers
- **Cache** de resultados
- **Logging** detalhado
- **Retry** inteligente

## 📊 Casos de Uso

### 💼 **E-commerce**

```php
// Calcular tributos em tempo real
$tributos = $fiscal->calcularTributos([
    'ncm' => '84715010',
    'origem' => 'SP',
    'destino' => 'RJ',
    'valor' => 1000.00
]);
```

### 🏭 **ERP/Contabilidade**  

```php
// Validar CNPJ antes de emitir NFe
$cnpj = $fiscal->consultarCNPJ('11222333000181');
if ($cnpj->sucesso) {
    // Proceder com emissão
}
```

### 🏢 **Software House**

```php
// Gerenciar múltiplos municípios
foreach ($clientes as $cliente) {
    $nfse = $fiscal->emitirNFSe($cliente->municipio, $dados);
}
```

## 🔧 Requisitos Técnicos

- **PHP** ^8.0
- **OpenSSL** (para certificados)
- **cURL** (para APIs externas)  
- **JSON** (manipulação de dados)

### 📦 **Dependências Principais**

```bash
nfephp-org/sped-nfe         # NFe/NFCe
guzzlehttp/guzzle          # HTTP Client  
monolog/monolog            # Logging
```

### 🧪 **Testes**

```bash
composer test
# ou
vendor/bin/phpunit
```

// App\Providers\AppServiceProvider.php
use NfePHP\NFe\Tools;
use freeline\FiscalCore\Adapters\NFeAdapter;

public function register()
{
    $this->app->bind(NFeAdapter::class, function () {
        $configJson = json_encode([ /*sua config NFe*/ ]);
        return new NFeAdapter(new Tools($configJson));
    });
}

```bash

Estrutura do projeto

```

src/
  Contracts/          # Interfaces (contratos de domínio)
    NotaFiscalInterface.php
    NotaServicoInterface.php
    ImpressaoInterface.php
    TributacaoInterface.php
    ProdutoInterface.php
    DocumentoInterface.php
    ConsultaPublicaInterface.php

  Adapters/           # Implementações que integram com bibliotecas externas
    NFeAdapter.php
    NFCeAdapter.php
    NFSeAdapter.php
    ImpressaoAdapter.php
    IBPTAdapter.php
    GTINAdapter.php
    DocumentoAdapter.php
    BrasilAPIAdapter.php

  Support/            # Classes utilitárias e gerenciamento centralizado
    CertificateManager.php    # Singleton para certificados digitais
    ConfigManager.php         # Singleton para configurações fiscais
    ToolsFactory.php          # Factory para NFePHP Tools
    IBPTAdapter.php
    GTINAdapter.php

## 🚨 Troubleshooting

### ❓ **Problemas Comuns**

| Erro | Solução |
| ------ | --------- |
| Certificado inválido | Verificar formato .pfx e senha |
| API indisponível | Usar fallbacks automáticos |
| Município não configurado | Adicionar em nfse-municipios.json |
| Quota excedida | Implementar cache local |

### 🔍 **Debug Mode**

```bash
export FISCAL_DEBUG=true
php examples/GuiaCompletoDeUso.php
```

### 📞 **Suporte**

- Ver exemplos em [examples/](examples/)
- Logs detalhados em modo debug  
- Issues no repositório

## 🗺️ Roadmap

### ✅ **Concluído**

- [x] Interface unificada (Facades)
- [x] Sistema de respostas padronizado
- [x] Error handling robusto
- [x] Múltiplos providers NFSe
- [x] Consultas públicas
- [x] Tributação IBPT

### 🔄 **Em Desenvolvimento**

- [ ] Interface web de administração
- [ ] Mais municípios NFSe  
- [ ] Integração com bancos de dados
- [ ] Dashboard de monitoramento

### 🎯 **Planejado**

- [ ] API REST para microserviços
- [ ] SDK JavaScript/Python
- [ ] Plugins para principais ERPs
- [ ] Certificação digital em nuvem

## 🛠️ Configuração Avançada

Para informações detalhadas sobre configuração de certificados e providers, consulte:

- 📄 [docs/providers-and-config.md](docs/providers-and-config.md)
- 📄 [config/nfse-municipios.json](config/nfse-municipios.json)

## 🧪 Estrutura de Testes

```bash
vendor/bin/phpunit
```

### Gerenciamento Centralizado (Singletons)

```php
use freeline\FiscalCore\Support\CertificateManager;
use freeline\FiscalCore\Support\ConfigManager;

// Certificados centralizados
$certManager = CertificateManager::getInstance();
$certManager->loadFromFile('/path/to/cert.pfx', 'password');

// Configurações centralizadas
$configManager = ConfigManager::getInstance();
$configManager->set('ambiente', 2); // homologação
```

## 📁 Estrutura do Projeto

```bash
src/
  Adapters/           # Integrações diretas com libs externas
    BrasilAPIAdapter.php
    DocumentoAdapter.php
    GTINAdapter.php
    IBPTAdapter.php
    ImpressaoAdapter.php
  Contracts/          # Interfaces padronizadas
  Facade/             # Interfaces unificadas
    FiscalFacade.php  # ✅ Interface principal
    NFeFacade.php     # ✅ NFe completa
    NFCeFacade.php    # ✅ NFCe completa
    NFSeFacade.php    # ✅ Multi-município
    ImpressaoFacade.php # ✅ DANFE/DANFSE
    TributacaoFacade.php # ✅ IBPT
  Support/            # Utilitários e helpers
examples/             # ✅ Exemplos práticos
  README.md           # ✅ Guia completo
  GuiaCompletoDeUso.php # ✅ Visão geral
  basico/             # ✅ Iniciantes
  avancado/           # ✅ Produção
```

$configManager->set('uf', 'SP');
$configManager->set('csc', 'SEU_CSC');

// Acesso em qualquer adapter
$isProduction = $configManager->isProduction();
$nfeConfig = $configManager->getNFeConfig();

```php

### ToolsFactory
```php
use freeline\FiscalCore\Support\ToolsFactory;

// Setup rápido para desenvolvimento
ToolsFactory::setupForDevelopment(['uf' => 'SP']);

// Cria Tools pré-configurados
$nfeTools = ToolsFactory::createNFeTools();
$adapter = new NFeAdapter($nfeTools);

// Validação de ambiente
$validation = ToolsFactory::validateEnvironment();
```

Status do projeto

- ✅ NFe Adapter: enviar/consultar/cancelar
- ✅ NFCe Adapter: emissão modelo 65
- ✅ Impressão (DANFE/DANFCE/MDFe/CTe)
- ✅ IBPT Adapter: cálculo de impostos
- ✅ GTIN Adapter: validação de códigos
- ✅ Documento Adapter: validação CPF/CNPJ
- ✅ BrasilAPI Adapter: consultas públicas
- ✅ Singletons: CertificateManager, ConfigManager, ToolsFactory
- 🔄 NFSe: arquitetura provider-based (stubs implementados)
- 🔄 Facades: orquestração de múltiplos adapters

Roadmap

📋 **Ver TODO completo:** [TODO.md](TODO.md)

🚀 **Sistema de Providers NFSe:**

- ✅ Estrutura base implementada (AbstractProvider, Registry, Config)
- ⏳ Implementação ABRASF v2 pendente
- 📚 Guia de retomada: [docs/PROVIDERS-RETOMADA.md](docs/PROVIDERS-RETOMADA.md)

**Próximas features:**

- [ ] Implementar montagem XML ABRASF v2 ([ver guia](docs/PROVIDERS-RETOMADA.md))
- [ ] Facades com APIs coesas (NFe/NFCe/NFSe/Impressão/Tributação)
- [ ] Service Provider para Laravel
- [ ] Middleware para validação automática
- [ ] Cache de consultas e configurações
- [ ] Publicar pacote no Packagist/GitHub Packages
- [ ] Documentação detalhada de cada Facade e Adapter

**Quick start para retomar:**

```bash
# Ver estrutura criada
tree src/Providers config/

# Rodar exemplo funcional
php scripts/exemplo-providers-nfse.php

# Ler guia completo
cat docs/PROVIDERS-RETOMADA.md
```

Contribuição

- Issues e PRs são bem-vindos. Antes de abrir PR:
  - Rode `vendor/bin/phpunit` e garanta verde.
  - Siga o estilo existente e mantenha mudanças focadas.

Licença

- MIT. Veja `composer.json`.
