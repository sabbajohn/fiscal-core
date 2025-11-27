# 📋 TODO - fiscal-core

## 🔥 PRIORIDADES IMEDIATAS

### NFSe Providers (Sistema implementado, falta completar)
- [ ] **Implementar montagem XML ABRASF v2** 
  - Arquivo: `src/Providers/NFSe/AbrasfV2Provider.php`
  - Método: `montarXmlRps()`
  - Ver: `docs/PROVIDERS-RETOMADA.md`

- [ ] **Integrar envio SOAP**
  - Arquivo: `src/Providers/NFSe/AbstractNFSeProvider.php`
  - Método: `emitir()`
  - Criar cliente SOAP e enviar XML

- [ ] **Implementar parser de resposta**
  - Arquivo: `src/Providers/NFSe/AbrasfV2Provider.php`
  - Método: `processarResposta()`
  - Extrair dados da resposta XML

- [ ] **Integrar assinatura digital**
  - Usar `CertificateManager` para assinar XML
  - Adicionar em `AbstractNFSeProvider::emitir()`

---

## 🎯 ROADMAP POR FEATURE

### 1. NFSe (🔄 EM ANDAMENTO)
**Estrutura base:** ✅ Completa
**Implementação:** ⏳ Pendente

- [x] Criar estrutura de Providers
- [x] Implementar ProviderRegistry
- [x] Criar AbstractNFSeProvider
- [x] Criar AbrasfV2Provider (esqueleto)
- [x] Criar JoinvilleProvider (exemplo)
- [x] Configuração externa (JSON)
- [x] Exemplo de uso
- [ ] Implementar montagem XML
- [ ] Implementar envio SOAP
- [ ] Implementar consulta
- [ ] Implementar cancelamento
- [ ] Testes unitários
- [ ] Adicionar mais municípios

**Próximo passo:** Implementar `montarXmlRps()` no `AbrasfV2Provider`

---

### 2. Certificado Digital (✅ FUNCIONANDO)
- [x] CertificateManager (singleton)
- [x] Carregamento automático via .env
- [x] Integração com ConfigManager
- [x] Validação e informações do certificado
- [ ] Resolver problema OpenSSL legacy (certificado específico)
- [ ] Adicionar suporte para certificado A3

---

### 3. ConfigManager (✅ FUNCIONANDO)
- [x] Singleton para configurações
- [x] Carregamento automático de .env
- [x] Integração com CertificateManager
- [x] Métodos getNFeConfig(), getNFSeConfig()
- [x] Validações de ambiente
- [ ] Cache de configurações
- [ ] Suporte para configuração Laravel

---

### 4. GTIN Adapter (✅ FUNCIONANDO)
- [x] Validação de GTIN
- [x] Integração com certificado
- [x] Métodos: validarGTIN(), checkGTIN()
- [x] Suporte para consultas remotas
- [ ] Cache de consultas
- [ ] Implementar buscarProduto() completo

---

### 5. Adapters Principais (✅ BÁSICO)
- [x] NFeAdapter
- [x] NFCeAdapter
- [x] NFSeAdapter (esqueleto)
- [x] ImpressaoAdapter
- [x] IBPTAdapter
- [x] DocumentoAdapter
- [x] BrasilAPIAdapter
- [ ] Melhorar tratamento de erros
- [ ] Adicionar logs
- [ ] Testes completos

---

### 6. Facades (🔄 STUBS)
- [ ] FiscalFacade
- [ ] NFeFacade
- [ ] NFCeFacade
- [ ] NFSeFacade
- [ ] ImpressaoFacade
- [ ] TributacaoFacade

**Objetivo:** Orquestrar múltiplos adapters com API simplificada

---

### 7. Laravel Integration (⏳ PLANEJADO)
- [ ] Service Provider
- [ ] Facades Laravel
- [ ] Configuração config/fiscal.php
- [ ] Middleware de validação
- [ ] Artisan commands
- [ ] Publicação de assets

---

### 8. Testes (🔄 PARCIAL)
- [x] GTINAdapterTest (básico)
- [ ] NFSeProviderTest
- [ ] CertificateManagerTest completo
- [ ] ConfigManagerTest completo
- [ ] Integration tests
- [ ] Cobertura > 80%

---

### 9. Documentação (🔄 EM ANDAMENTO)
- [x] README.md principal
- [x] docs/providers-and-config.md
- [x] docs/PROVIDERS-RETOMADA.md
- [ ] Documentar cada Adapter
- [ ] Documentar cada Facade
- [ ] Exemplos de uso completos
- [ ] Guia de integração Laravel
- [ ] API Reference

---

### 10. DevOps & CI/CD (⏳ PLANEJADO)
- [ ] GitHub Actions (testes)
- [ ] PHPStan (análise estática)
- [ ] PHP-CS-Fixer (code style)
- [ ] Codecov (cobertura)
- [ ] Semantic versioning
- [ ] CHANGELOG.md automático

---

## 🐛 BUGS CONHECIDOS

- [ ] OpenSSL legacy: `error:0308010C:digital envelope routines::unsupported`
  - Workaround: Reconverter certificado
  - Ver: `scripts/fix-legacy-cert.php`
  
- [ ] PHPUnit: `--testdox` não funciona em alguns terminais
  - Workaround: Usar `php vendor/bin/phpunit` diretamente

---

## 💡 MELHORIAS FUTURAS

- [ ] Cache distribuído (Redis) para consultas
- [ ] Fila de processamento (RabbitMQ) para emissões
- [ ] Webhooks para eventos (NFe autorizada, cancelada, etc.)
- [ ] Dashboard para monitoramento
- [ ] API REST para microserviços
- [ ] SDK JavaScript/TypeScript
- [ ] CLI independente (Composer bin)

---

## 📦 PUBLICAÇÃO

- [ ] Preparar para Packagist
- [ ] Configurar GitHub Packages
- [ ] Versão 1.0.0 estável
- [ ] Release notes
- [ ] Site de documentação (GitHub Pages)

---

## 🎓 APRENDIZADO & REFERÊNCIAS

### Documentação Oficial
- NFePHP: https://github.com/nfephp-org
- ABRASF: Manual NFSe v2
- Receita Federal: Manual NFe v4.0

### Ferramentas Úteis
- `scripts/exemplo-providers-nfse.php` - Testar providers
- `scripts/diagnostico-certificado.php` - Debug certificados
- `scripts/debug-config.php` - Debug configurações
- `docs/PROVIDERS-RETOMADA.md` - Guia de retomada

---

## ✅ QUICK START PARA RETOMAR

1. **Ver estrutura criada:**
   ```bash
   tree src/Providers config/
   ```

2. **Rodar exemplo:**
   ```bash
   php scripts/exemplo-providers-nfse.php
   ```

3. **Ler guia de retomada:**
   ```bash
   cat docs/PROVIDERS-RETOMADA.md
   ```

4. **Começar implementação:**
   - Abrir: `src/Providers/NFSe/AbrasfV2Provider.php`
   - Buscar: `TODO: Implementar estrutura XML conforme ABRASF v2`
   - Implementar: método `montarXmlRps()`

---

**Última atualização:** 25/11/2025  
**Status geral:** 🟡 Estrutura pronta, implementações pendentes  
**Próxima tarefa:** Implementar `AbrasfV2Provider::montarXmlRps()`
