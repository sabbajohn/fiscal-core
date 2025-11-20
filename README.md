📦 Fiscal Core

Pacote PHP para emissão e consulta de documentos fiscais eletrônicos (NF-e, NFC-e, NFSe), geração de impressos (DANFE, DANFCE, MDFe, CTe) e integrações tributárias (IBPT, GTIN). Construído sobre os pacotes nfephp-org, com arquitetura baseada em Adapters e Facades para desacoplamento e simplicidade.

![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.1-777bb4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Status](https://img.shields.io/badge/status-WIP-orange)
![Tests](https://img.shields.io/badge/tests-phpunit%20%5E10-blue)

—

Sumário
- Requisitos
- Instalação
- Desenvolvimento local
- Uso rápido
- Exemplos de uso
- Estrutura do projeto
- Configuração e certificados
- Testes
- Status do projeto
- Roadmap
- Contribuição
- Licença

—

Requisitos
- PHP >= 8.1
- Dependências nfephp-org conforme `composer.json` (NFe, IBPT, GTIN, DA)
- Extensões e requisitos adicionais conforme documentação oficial dos pacotes nfephp-org

Instalação
- Projeto via Packagist (quando publicado):
  
  ```bash
  composer require freeline/fiscal-core
  ```

- Desenvolvimento local (path repository):
  
  1) No `composer.json` do seu microserviço:
  
  ```json
  {
    "repositories": [
      { "type": "path", "url": "../fiscal-core" }
    ]
  }
  ```
  
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
  
  ```bash
  vendor/bin/phpunit
  ```

Uso rápido
- O projeto fornece Adapters para integração direta com as libs nfephp-org. Alguns Facades existem como stubs e ainda serão implementados. Abaixo, exemplos com Adapters já funcionais.

Exemplos de uso

1) NFe: emitir, consultar e cancelar

```php
use NfePHP\NFe\Tools;
use freeline\FiscalCore\Adapters\NFeAdapter;

// Configuração esperada pelo NfePHP (veja a doc oficial para campos e certificados)
$configJson = json_encode([
    // ... suas configurações NFe (certificado, ambiente, CNPJ, UF, etc.)
]);

$tools = new Tools($configJson);
$nfe = new NFeAdapter($tools);

// Emissão (exemplo ilustrativo)
$xmlAssinadoOuDados = [ /* seu payload/estrutura compatível */ ];
$respostaEnvio = $nfe->emitir($xmlAssinadoOuDados);

// Consulta por chave
$respostaConsulta = $nfe->consultar('NFe-chave-44-dígitos');

// Cancelamento
$ok = $nfe->cancelar('NFe-chave-44-dígitos', 'Motivo do cancelamento', 'protocolo');
```

2) Impressão: gerar DANFE/DANFCE/MDFe/CTe

```php
use freeline\FiscalCore\Adapters\ImpressaoAdapter;

$imp = new ImpressaoAdapter();

// A partir de um XML autorizado
$danfePdf = $imp->gerarDanfe($xmlNfe);
$danfcePdf = $imp->gerarDanfce($xmlNfce);
$damdfePdf = $imp->gerarMdfe($xmlMdfe);
$dactePdf = $imp->gerarCte($xmlCte);

// Salvar como PDF (exemplo)
file_put_contents('danfe.pdf', $danfePdf);
```

Observações
- As classes `Facade` presentes são atualmente placeholders e serão implementadas para orquestrar múltiplos Adapters e expor APIs simplificadas.
- Para uso em Laravel, você pode registrar bindings no container manualmente até que um Service Provider oficial seja disponibilizado:

```php
// App\Providers\AppServiceProvider.php
use NfePHP\NFe\Tools;
use freeline\FiscalCore\Adapters\NFeAdapter;

public function register()
{
    $this->app->bind(NFeAdapter::class, function () {
        $configJson = json_encode([ /* sua config NFe */ ]);
        return new NFeAdapter(new Tools($configJson));
    });
}
```

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

  Facade/             # Facades (stubs por enquanto)
    FiscalFacade.php
    NFeFacade.php
    NFCeFacade.php
    NFSeFacade.php
    ImpressaoFacade.php
    TributacaoFacade.php
```

Configuração e certificados
- Para emissão/consulta de NF-e, o NfePHP exige configuração detalhada (certificado A1/A3, ambiente, CSC/CSRT quando aplicável, UF, CNPJ, etc.).
- Recomendação: seguir a documentação oficial do NfePHP para montar o `config.json`/array e carregar certificados.
- Links úteis (nfephp-org):
  - NFe: https://github.com/nfephp-org/sped-nfe
  - DA (DANFE/DANFCE/MDFe/CTe): https://github.com/nfephp-org/sped-da
  - IBPT: https://github.com/nfephp-org/sped-ibpt
  - GTIN: https://github.com/nfephp-org/sped-gtin

Testes
- O projeto utiliza PHPUnit.

```bash
vendor/bin/phpunit
```

## Gerenciamento Centralizado (Singletons)

O fiscal-core inclui singletons para centralizar configurações e certificados:

### CertificateManager
```php
use freeline\FiscalCore\Support\CertificateManager;

// Carrega certificado uma única vez
$certManager = CertificateManager::getInstance();
$certManager->loadFromFile('/path/to/cert.pfx', 'password');

// Reutiliza em qualquer lugar
$cnpj = $certManager->getCnpj();
$isValid = $certManager->isValid();
$daysLeft = $certManager->getDaysUntilExpiration();
```

### ConfigManager
```php
use freeline\FiscalCore\Support\ConfigManager;

// Configurações centralizadas
$configManager = ConfigManager::getInstance();
$configManager->set('ambiente', 2); // homologação
$configManager->set('uf', 'SP');
$configManager->set('csc', 'SEU_CSC');

// Acesso em qualquer adapter
$isProduction = $configManager->isProduction();
$nfeConfig = $configManager->getNFeConfig();
```

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
- [ ] Implementar providers NFSe específicos por município
- [ ] Facades com APIs coesas (NFe/NFCe/NFSe/Impressão/Tributação)
- [ ] Service Provider para Laravel
- [ ] Middleware para validação automática
- [ ] Cache de consultas e configurações
- [ ] Publicar pacote no Packagist/GitHub Packages
- [ ] Documentação detalhada de cada Facade e Adapter.

Contribuição
- Issues e PRs são bem-vindos. Antes de abrir PR:
  - Rode `vendor/bin/phpunit` e garanta verde.
  - Siga o estilo existente e mantenha mudanças focadas.

Licença
- MIT. Veja `composer.json`.

