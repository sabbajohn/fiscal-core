# Configuração de Testes Unitários

## 🎯 Visão Geral

Os testes foram reestruturados para focar em **regras de negócio tributárias** e **validações fiscais**, substituindo os antigos arquivos de debug verbosos por testes PHPUnit profissionais.

## 📁 Estrutura dos Testes

```bash
tests/
├── Unit/                           # Testes unitários focados
│   ├── Tributacao/
│   │   ├── ICMSCalculationTest.php # Cálculos ICMS por UF
│   │   └── NCMValidationTest.php   # Validação NCM e regras
│   ├── NFe/
│   │   └── XMLValidationTest.php   # Validação XML NFe
│   ├── NFSe/
│   │   └── ProviderConfigTest.php  # Configuração municípios
│   └── Support/
│       └── ResponseHandlingTest.php # Sistema de respostas
└── Integration/                    # Testes de integração com APIs externas
```

## 🧪 Executando os Testes

### Todos os testes

```bash
composer test
# ou
vendor/bin/phpunit
```

### Por categoria

```bash
# Apenas testes de tributação
vendor/bin/phpunit --testsuite Tributacao

# Apenas NFe
vendor/bin/phpunit --testsuite NFe

# Apenas NFSe  
vendor/bin/phpunit --testsuite NFSe
```

### Com coverage

```bash
vendor/bin/phpunit --coverage-html coverage-html
```

## 📋 Cobertura de Testes

### ✅ Tributação (ICMSCalculationTest)

- Cálculo ICMS operações internas
- Cálculo ICMS operações interestaduais  
- Substituição tributária
- Validação de NCM obrigatório
- Validação de UF origem/destino

### ✅ Validação NCM (NCMValidationTest)  

- Formato correto de NCM (8 dígitos)
- Rejeição de formatos inválidos
- Consulta dados NCM via API
- Identificação produtos sujeitos a ST
- Alíquotas IPI por NCM
- Hierarquia NCM (capítulo/posição/item)

### ✅ Validação XML NFe (XMLValidationTest)

- Estrutura básica XML NFe
- Elementos obrigatórios
- Formato chave de acesso
- Validação CNPJ emitente
- Consistência de totais
- CST ICMS válidos

### ✅ Configuração NFSe (ProviderConfigTest)

- Carregamento configuração por município
- Listagem municípios disponíveis
- Validação configuração completa
- Detecção configurações incompletas
- Regras específicas por município
- Fallbacks para municípios similares

### ✅ Sistema Respostas (ResponseHandlingTest)

- Criação respostas sucesso/erro
- Metadata em respostas
- Tratamento de exceções
- Timeout de operações
- Retry automático
- Cache de respostas
- Serialização JSON

## ⚙️ Configuração para Testes

### Environment Variables

```bash
# Debug nos testes
export FISCAL_DEBUG=false

# Habilitar testes com APIs externas
export ENABLE_EXTERNAL_TESTS=true

# IBPT para testes tributários (opcional)
export IBPT_TEST_CNPJ="11222333000181"
export IBPT_TEST_TOKEN="seu_token_teste"

# Certificado para testes NFe (opcional)
export TEST_CERT_PATH="/path/to/test.pfx"
export TEST_CERT_PASSWORD="senha"
```

### Mocks vs APIs Reais

- **Unit tests**: Usam mocks para isolamento
- **Integration tests**: Testam APIs reais (quando `ENABLE_EXTERNAL_TESTS=true`)

## 🎯 Regras de Negócio Testadas

### 💰 Tributação

- **ICMS interno SP**: 18%
- **ICMS interestadual**: 12%
- **Substituição tributária**: Bebidas alcoólicas (NCM 22071000)
- **IPI**: Equipamentos eletrônicos isento, bebidas 20%

### 📄 NFe

- **Chave acesso**: 44 dígitos numéricos
- **CNPJ**: Validação dígitos verificadores
- **Totais**: Consistência soma itens vs total geral
- **CST**: Códigos válidos ICMS (00,10,20,30,40,41,50,51,60,70,90)

### 🏘️ NFSe

- **Municípios**: Configuração obrigatória por código IBGE
- **Providers**: São Paulo, Curitiba, BH configurados
- **Schemas**: Versão formato X.Y por município
- **Ambientes**: Homologação/Produção por configuração

## 📊 Qualidade do Código

### Métricas

- **Coverage mínimo**: 80%
- **Complexidade ciclomática**: < 10
- **Assertions por teste**: 3-5
- **Isolamento**: Cada teste independente

### Padrões

- **Arrange, Act, Assert**: Estrutura clara
- **Nomes descritivos**: `deve_calcular_icms_operacao_interna_sp`
- **Dados de teste realistas**: NCMs, CNPJs, UFs válidos  
- **Error messages claros**: Contexto específico de falha

## 🛠️ Debugging Testes

### Executar teste específico

```bash
vendor/bin/phpunit --filter "deve_calcular_icms_operacao_interna_sp"
```

### Debug verboso

```bash
vendor/bin/phpunit --testdox --verbose
```

### Parar no primeiro erro

```bash
vendor/bin/phpunit --stop-on-failure
```

## 🎉 Benefícios da Nova Estrutura

- ✅ **Robustez**: Validação automática de regras tributárias
- ✅ **Credibilidade**: Testes profissionais demonstram qualidade  
- ✅ **Segurança**: Detecção precoce de regressões
- ✅ **Documentação**: Testes servem como especificação
- ✅ **CI/CD**: Integração com pipelines automatizados
- ✅ **Manutenibilidade**: Refatoração segura com testes cobrindo

---

**Migração concluída:** De arquivos debug verbosos para testes unitários focados em regras de negócio tributárias! 🚀
