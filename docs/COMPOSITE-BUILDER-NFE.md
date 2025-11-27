# Sistema Composite + Builder para NFe/NFCe

## 📋 Visão Geral

Sistema completo para construção de NFe/NFCe usando padrões **Composite** + **Builder**, integrado com **NFePHP Make**.

## 🏗️ Arquitetura

### Padrão Composite
- **NotaNodeInterface**: Interface base para todos os nodes
- **Nodes**: IdentificacaoNode, EmitenteNode, DestinatarioNode, ProdutoNode, ImpostoNode, PagamentoNode
- **NotaFiscal**: Composite root que agrega todos os nodes

### Padrão Builder
- **NotaFiscalBuilder**: Constrói NotaFiscal a partir de arrays/JSON
- **Factory Methods**: DTOs com construtores estáticos convenientes

### DTOs (Data Transfer Objects)
- **IdentificacaoDTO**: Dados da tag `<ide>`
- **EmitenteDTO**: Dados da tag `<emit>`
- **DestinatarioDTO**: Dados da tag `<dest>`
- **ProdutoDTO**: Dados da tag `<prod>` dentro de `<det>`
- **IcmsDTO**: Impostos ICMS (Simples Nacional, regime normal)
- **PisDTO**: Impostos PIS
- **CofinsDTO**: Impostos COFINS
- **PagamentoDTO**: Formas de pagamento (tag `<pag>`)

## 🎯 Exemplos de Uso

### 1. Construção via Builder (a partir de array)

```php
$dadosNota = [
    'identificacao' => [
        'cUF' => 41,
        'cNF' => 12345678,
        'natOp' => 'VENDA DE MERCADORIA',
        'mod' => 65, // NFCe
        'serie' => 1,
        'nNF' => 123,
        'cMunFG' => 4106902,
        'tpAmb' => 2,
    ],
    'emitente' => [
        'cnpj' => '12345678000190',
        'razaoSocial' => 'EMPRESA EXEMPLO LTDA',
        'nomeFantasia' => 'EMPRESA EXEMPLO',
        'inscricaoEstadual' => '1234567890',
        'logradouro' => 'RUA EXEMPLO',
        'numero' => '123',
        'bairro' => 'CENTRO',
        'codigoMunicipio' => 4106902,
        'municipio' => 'CURITIBA',
        'uf' => 'PR',
        'cep' => '80000000',
        'crt' => 1, // Simples Nacional
    ],
    'destinatario' => [
        'cpfCnpj' => '12345678901',
        'nome' => 'CONSUMIDOR FINAL',
        'indIEDest' => 9,
    ],
    'itens' => [
        [
            'produto' => [
                'codigo' => 'PROD001',
                'descricao' => 'PRODUTO EXEMPLO',
                'ncm' => '12345678',
                'cfop' => '5102',
                'unidade' => 'UN',
                'quantidade' => 2.0,
                'valorUnitario' => 50.00,
                'valorTotal' => 100.00,
            ],
            'impostos' => [
                'icms' => [
                    'cst' => '102', // Simples Nacional
                    'orig' => 0,
                ],
                'pis' => ['cst' => '49'],
                'cofins' => ['cst' => '49'],
            ],
        ],
    ],
    'pagamentos' => [
        ['tPag' => '01', 'vPag' => 100.00], // Dinheiro
    ],
];

$nota = NotaFiscalBuilder::fromArray($dadosNota)->build();
$nota->validate();
$xml = $nota->toXml();
```

### 2. Construção manual com Factory Methods

```php
$nota = new NotaFiscal();

// Identificação (factory method)
$identificacao = IdentificacaoDTO::forNFCe(
    cUF: 41,
    natOp: 'VENDA',
    nNF: 456,
    cMunFG: 4106902
);
$nota->addNode(new IdentificacaoNode($identificacao));

// Emitente
$emitente = new EmitenteDTO(
    cnpj: '12345678000190',
    razaoSocial: 'EMPRESA LTDA',
    nomeFantasia: 'EMPRESA',
    inscricaoEstadual: '1234567890',
    logradouro: 'RUA EXEMPLO',
    numero: '123',
    bairro: 'CENTRO',
    codigoMunicipio: '4106902',
    nomeMunicipio: 'CURITIBA',
    uf: 'PR',
    cep: '80000000',
    crt: 1
);
$nota->addNode(new EmitenteNode($emitente));

// Destinatário (factory method)
$destinatario = DestinatarioDTO::consumidorFinal('12345678901', 'JOAO SILVA');
$nota->addNode(new DestinatarioNode($destinatario));

// Produto (factory method)
$produto = ProdutoDTO::simple(
    item: 1,
    codigo: 'PROD123',
    descricao: 'PRODUTO SIMPLES',
    ncm: '12345678',
    cfop: '5102',
    quantidade: 3,
    valorUnitario: 25.00
);
$nota->addNode(new ProdutoNode($produto));

// Impostos (factory methods)
$icms = IcmsDTO::simplesNacionalSemCredito();
$nota->addNode(new ImpostoNode(1, $icms));

// Pagamento (factory method)
$pagamento = PagamentoDTO::dinheiro(75.00);
$nota->addNode(new PagamentoNode($pagamento));

$nota->validate();
$xml = $nota->toXml();
```

### 3. NFCe com múltiplos pagamentos

```php
$nota = new NotaFiscal();

$id = IdentificacaoDTO::forNFCe(41, 'VENDA', 789, 4106902);
$nota->addNode(new IdentificacaoNode($id));

// ... adicionar emitente, destinatário, produto, impostos ...

// Múltiplos pagamentos
$pag1 = PagamentoDTO::dinheiro(20.00);
$pag2 = PagamentoDTO::cartaoDebito(10.00);
$nota->addNode(new PagamentoNode($pag1, $pag2));

$nota->validate();
```

## 🏷️ Factory Methods Disponíveis

### IdentificacaoDTO
```php
IdentificacaoDTO::forNFe($cUF, $natOp, $nNF, $cMunFG, $idDest)
IdentificacaoDTO::forNFCe($cUF, $natOp, $nNF, $cMunFG)
```

### DestinatarioDTO
```php
DestinatarioDTO::consumidorFinal($cpf, $nome)
```

### ProdutoDTO
```php
ProdutoDTO::simple($item, $codigo, $descricao, $ncm, $cfop, $quantidade, $valorUnitario)
```

### IcmsDTO
```php
IcmsDTO::simplesNacionalSemCredito($orig = 0)
IcmsDTO::simplesNacionalComCredito($pCredSN, $vCredICMSSN, $orig = 0)
IcmsDTO::icms00($vBC, $pICMS, $vICMS, $orig = 0)
IcmsDTO::icmsIsento($orig = 0)
```

### PisDTO
```php
PisDTO::naoCumulativo($vBC, $pPIS, $vPIS)
PisDTO::aliquotaZero()
PisDTO::outrasOperacoes()
PisDTO::semIncidencia()
```

### CofinsDTO
```php
CofinsDTO::naoCumulativo($vBC, $pCOFINS, $vCOFINS)
CofinsDTO::aliquotaZero()
CofinsDTO::outrasOperacoes()
CofinsDTO::semIncidencia()
```

### PagamentoDTO
```php
PagamentoDTO::dinheiro($valor)
PagamentoDTO::cartaoCredito($valor, $cnpjCredenciadora, $bandeira, $autorizacao)
PagamentoDTO::cartaoDebito($valor, $cnpjCredenciadora, $bandeira, $autorizacao)
PagamentoDTO::pix($valor)
```

## ✅ Validações

Cada Node implementa validações automáticas:
- **IdentificacaoNode**: Valida natureza operação, modelo, número
- **EmitenteNode**: Valida CNPJ, razão social, IE, CRT
- **DestinatarioNode**: Valida CPF/CNPJ, nome, indIEDest
- **ProdutoNode**: Valida código, descrição, NCM, CFOP, quantidade, valor
- **ImpostoNode**: Valida CST do ICMS
- **PagamentoNode**: Valida tipo e valor do pagamento

## 🔧 Integração com NFePHP

Cada Node sabe como adicionar-se ao objeto `Make` do NFePHP:

```php
interface NotaNodeInterface
{
    public function addToMake(Make $make): void;
    public function validate(): bool;
    public function getNodeType(): string;
}
```

O método `NotaFiscal::toMake()` retorna um objeto `NFePHP\NFe\Make` populado:

```php
$nota = NotaFiscalBuilder::fromArray($dados)->build();
$make = $nota->getMake();

// Usar o objeto Make para assinar, transmitir, etc
$tools = new Tools($config);
$signed = $tools->signNFe($make->getXML());
```

## 📂 Estrutura de Arquivos

```
src/Adapters/NF/
├── NotaNodeInterface.php       # Interface base
├── NotaFiscal.php               # Composite root
├── NotaFiscalBuilder.php        # Builder
├── DTO/
│   ├── IdentificacaoDTO.php
│   ├── EmitenteDTO.php
│   ├── DestinatarioDTO.php
│   ├── ProdutoDTO.php
│   ├── IcmsDTO.php
│   ├── PisDTO.php
│   ├── CofinsDTO.php
│   └── PagamentoDTO.php
└── Nodes/
    ├── IdentificacaoNode.php
    ├── EmitenteNode.php
    ├── DestinatarioNode.php
    ├── ProdutoNode.php
    ├── ImpostoNode.php
    └── PagamentoNode.php
```

## 🎓 Vantagens do Sistema

1. **Type-Safe**: Usa DTOs tipados, evita erros de digitação
2. **Incremental**: Permite construção passo a passo
3. **Validável**: Cada componente pode ser validado independentemente
4. **Reutilizável**: Factory methods para casos comuns
5. **Testável**: Cada Node pode ser testado isoladamente
6. **Extensível**: Fácil adicionar novos Nodes (TransporteNode, TotalNode, etc)
7. **Flexível**: Suporta tanto construção via array quanto manual
8. **Integrado**: Compatível com NFePHP Make

## 🚀 Próximos Passos

- [ ] Adicionar TotalNode (tag `<total>`)
- [ ] Adicionar TransporteNode (tag `<transp>`)
- [ ] Adicionar InformacoesAdicionaisNode (tag `<infAdic>`)
- [ ] Suportar múltiplos produtos no Builder
- [ ] Adicionar validações de totais (soma dos itens)
- [ ] Adicionar factory methods para casos comuns de CFOP
- [ ] Integrar com NFeAdapter/NFCeAdapter existentes
