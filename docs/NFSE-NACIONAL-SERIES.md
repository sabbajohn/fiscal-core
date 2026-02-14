# NFSe Nacional - Regras de Série

## Faixas de Série por Tipo de Emissão

A série da DPS (Declaração de Prestação de Serviços) no ambiente nacional segue faixas específicas de acordo com o tipo de emissor (`tpEmit`):

### tpEmit = 1 (Aplicativo Próprio / Prestador)
- **Faixa válida:** `00900` a `00999`
- **Default da biblioteca:** `00900`
- Uso: sistemas próprios de emissão do prestador

### tpEmit = 2 (Emissor Web)
- **Faixa válida:** `00990` a `00999`
- **Default da biblioteca:** `00990`
- Uso: emissão via portal web da prefeitura/SEFIN

### tpEmit = 3 (Outros)
- **Faixa válida:** `00001` a `00999`
- **Default da biblioteca:** `00001`
- Uso: outros tipos de emissão

## Formato e Validação

- Série é sempre um número inteiro com **5 dígitos** (com padding à esquerda se necessário)
- Exemplo: `00900`, `01000`, `00001`
- A biblioteca valida automaticamente se a série está na faixa correta para o `tpEmit` informado

## Como Configurar

### Via Payload de Emissão

```php
$dados = [
    'tpEmit' => '1',        // Aplicativo próprio
    'serie' => '00950',     // Entre 00900-00999
    'nDPS' => '12345',
    // ... demais campos
];

$nfse->emitir($dados);
```

### Default Automático

Se você **não informar** a série, a biblioteca usa:
- `00900` para `tpEmit=1`
- `00990` para `tpEmit=2`
- `00001` para `tpEmit=3`

## Erros Comuns

### RNG9999 - Série fora da faixa
```
Para tpEmit=1 (aplicativo próprio), série deve estar entre 00900 e 00999.
```

**Solução:** ajuste a série para o range correto ou omita para usar o default.

### Exemplo INCORRETO
```php
$dados = [
    'tpEmit' => '1',
    'serie' => '00001',  // ❌ Fora da faixa para tpEmit=1
];
```

### Exemplo CORRETO
```php
$dados = [
    'tpEmit' => '1',
    'serie' => '00900',  // ✅ Dentro da faixa
];
```

## Referência

Baseado no manual técnico da NFSe Nacional v1.00 e nas especificações de leiaute da SEFIN.
