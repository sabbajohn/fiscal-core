# 📚 FISCAL-CORE EXAMPLES - Guia de Exemplos

## 🎯 Organização dos Exemplos

Esta pasta foi reestruturada para demonstrar o uso prático da biblioteca `fiscal-core` após instalação via composer, substituindo os antigos scripts de desenvolvimento.

### 📁 Estrutura Organizada

```bash
examples/
├── 📄 GuiaCompletoDeUso.php          # Visão geral de todas as funcionalidades
├── 📁 basico/                        # Exemplos para iniciantes
│   ├── 01-primeira-consulta.php      # Primeira operação fiscal (NCM)
│   ├── 02-status-sistema.php         # Verificação de status fiscal
│   ├── 03-consultas-publicas.php     # APIs públicas (CEP, CNPJ, bancos) - UtilsFacade
│   └── 04-operacoes-fiscais.php      # Operações fiscais com NFe - FiscalFacade
├── 📁 avancado/                      # Exemplos para uso profissional
│   ├── 01-multiplos-municipios.php   # Gerenciar múltiplos municípios NFSe
│   └── 02-error-handling.php         # Tratamento robusto de erros
└── 🧪 Testes unitários               # Validação de regras de negócio
    └── tests/Unit/                   # Testes focados em tributação
```

## 🚀 Como Começar

### 1. **Primeira Experiência**

```bash
php examples/GuiaCompletoDeUso.php
```

### 2. **Aprendizado Progressivo**

```bash
# Operações fiscais (contexto NFe/NFCe/NFSe)
php examples/basico/01-primeira-consulta.php    # NCM para tributação
php examples/basico/02-status-sistema.php       # Status SEFAZ
php examples/basico/04-operacoes-fiscais.php    # Operações fiscais completas

# Consultas públicas (utilitários)
php examples/basico/03-consultas-publicas.php   # CEP, CNPJ, bancos, validações

# Exemplos avançados (produção)
php examples/avancado/01-multiplos-municipios.php
php examples/avancado/02-error-handling.php
```

## 📋 Funcionalidades por Exemplo

| Exemplo | O que demonstra | Interface |
| --------- | ----------------- | ----------- |
| **GuiaCompletoDeUso** | Visão geral completa | Fiscal + Utils |
| **01-primeira-consulta** | Operação fiscal básica (NCM) | FiscalFacade |
| **02-status-sistema** | Verificação componentes fiscais | FiscalFacade |
| **03-consultas-publicas** | CEP, CNPJ, bancos, validações | UtilsFacade |
| **04-operacoes-fiscais** | Contexto fiscal completo | FiscalFacade |
| **01-multiplos-municipios** | Gestão NFSe multi-município | FiscalFacade |
| **02-error-handling** | Tratamento robusto de erros | Ambos |

## 🎭 Separação de Responsabilidades

### ✅ **Agora (nova estrutura)**

- **FiscalFacade** - Operações fiscais (NFe, NFCe, NFSe, IBPT, SEFAZ)
- **UtilsFacade** - Consultas públicas (BrasilAPI, validações, utilitários)
- Responsabilidades claras e bem definidas
- Expansão sem poluir contexto fiscal

### ❌ **Antes (misturado)**

- Tudo no FiscalFacade
- Consultas públicas misturadas com operações fiscais
- Contexto poluído com utilitários
- Difícil manutenção e expansão

## 🛠️ Configuração Opcional

Para exemplos mais avançados, você pode configurar:

### 📜 **Certificados NFe/NFCe**

```bash
# Coloque seus certificados em:
certs/certificado.pfx
# Configure no ambiente
```

### 💰 **IBPT (Tributação)**

```bash
export IBPT_CNPJ="11222333000181"
export IBPT_TOKEN="seu_token_ibpt"
export IBPT_UF="SP"
```

### 🏘️ **NFSe Municípios**

```bash
# Edite config/nfse-municipios.json
# Adicione seus municípios específicos
```

## 🎯 Casos de Uso por Público

### 👨‍💻 **Desenvolvedores Iniciantes**

1. `GuiaCompletoDeUso.php` - Visão geral
2. `basico/` - Exemplos sem configuração
3. Documentação no README.md

### 🏢 **Equipes de Produção**

1. `avancado/` - Patterns profissionais
2. Error handling robusto
3. Múltiplos municípios
4. Logging e monitoramento

### 🏭 **Software Houses**

1. `01-multiplos-municipios.php` - Multi-tenant
2. `02-error-handling.php` - Recuperação de falhas
3. Configuração dinâmica de clientes

### 📊 **Contabilidade/ERP**

1. Consultas públicas em massa
2. Validação prévia de dados
3. Fallbacks por município
4. Auditoria de operações

## 📈 Próximos Passos

Após dominar os exemplos:

1. **Implementar** em sua aplicação
2. **Configurar** certificados e tokens
3. **Personalizar** para seus municípios
4. **Monitorar** erros e performance
5. **Escalar** conforme necessário

## 💡 Dicas de Uso

### ✅ **Faça**

- Comece sempre pelos exemplos básicos
- Teste sem configuração primeiro
- Implemente error handling desde o início
- Use a interface unificada (FiscalFacade)
- Monitore logs de operação

### ❌ **Evite**

- Usar scripts/ em produção
- Ignorar verificação de status
- Processar sem validar dados
- Misturar lógicas de diferentes municípios
- Deixar de tratar erros específicos

---

🎉 **A biblioteca fiscal-core está pronta para uso em produção com máxima confiabilidade!**
