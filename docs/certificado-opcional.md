# Certificado Digital Opcional

A partir desta versão, o Fiscal Core suporta operações de consulta e status sem exigir um certificado digital válido. Isso facilita o uso em cenários de desenvolvimento, testes e operações que não requerem assinatura digital.

## Contexto

Anteriormente, todas as operações exigiam um certificado digital carregado, mesmo para operações simples como verificar o status do SEFAZ ou consultar uma nota fiscal pela chave. Isso tornava o desenvolvimento e testes mais complexos, pois exigia sempre um certificado válido, mesmo para operações de leitura.

## Operações que NÃO requerem certificado

As seguintes operações agora funcionam **sem** exigir um certificado digital válido:

- **`sefazStatus()`**: Verificação do status/disponibilidade dos serviços SEFAZ
- **`consultar()`**: Consulta de NFe/NFCe pela chave de acesso (apenas leitura)

Internamente, essas operações usam uma instância de `Tools` criada com `requireCertificate = false`, que:
- Usa o certificado real se disponível
- Gera um certificado auto-assinado temporário se não houver certificado carregado
- Fornece valores dummy para CNPJ e Razão Social na configuração

## Operações que EXIGEM certificado

As seguintes operações **continuam exigindo** um certificado digital válido:

- **`emitir()`**: Emissão de NFe/NFCe (requer assinatura digital)
- **`cancelar()`**: Cancelamento de NFe/NFCe (requer assinatura digital)
- **`inutilizar()`**: Inutilização de numeração (requer assinatura digital)
- Qualquer operação que envolva assinatura de XML

## Uso

### Exemplo 1: Verificar Status SEFAZ sem Certificado

```php
use freeline\FiscalCore\Adapters\NFeAdapter;
use freeline\FiscalCore\Support\ToolsFactory;

// Configura ambiente de desenvolvimento (sem certificado)
ToolsFactory::setupForDevelopment([
    'uf' => 'SP',
    'ambiente' => 2 // homologação
]);

// Cria adapter - NÃO requer certificado no construtor
$nfe = new NFeAdapter();

// Verifica status do SEFAZ - funciona sem certificado!
try {
    $status = $nfe->sefazStatus('SP', 2);
    echo "SEFAZ Status: " . $status;
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage();
}
```

### Exemplo 2: Consultar NFe sem Certificado

```php
use freeline\FiscalCore\Adapters\NFeAdapter;

$nfe = new NFeAdapter();

// Consulta NFe pela chave - funciona sem certificado!
$chave = '35210112345678901234550010000000011234567890';
try {
    $resultado = $nfe->consultar($chave);
    echo "Resultado da consulta: " . $resultado;
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage();
}
```

### Exemplo 3: Emitir NFe - REQUER Certificado

```php
use freeline\FiscalCore\Adapters\NFeAdapter;
use freeline\FiscalCore\Support\CertificateManager;
use freeline\FiscalCore\Support\ToolsFactory;

// Configurar ambiente
ToolsFactory::setupForDevelopment(['uf' => 'SP']);

// OBRIGATÓRIO: Carregar certificado para operações de emissão
$certManager = CertificateManager::getInstance();
$certManager->loadFromFile('/path/to/certificado.pfx', 'senha_do_certificado');

// Agora pode emitir
$nfe = new NFeAdapter();
$resultado = $nfe->emitir($dadosDaNota);
```

## Implementação Técnica

### Arquitetura

Os adapters (`NFeAdapter` e `NFCeAdapter`) agora possuem dois métodos privados para obter instâncias de `Tools`:

1. **`getTools()`**: Cria `Tools` com `requireCertificate = true`
   - Usado por: `emitir()`, `cancelar()`, `inutilizar()`
   - Lança exceção se certificado não estiver carregado ou for inválido

2. **`getToolsOptionalCert()`**: Cria `Tools` com `requireCertificate = false`
   - Usado por: `sefazStatus()`, `consultar()`
   - Usa certificado real se disponível, ou gera certificado temporário

### Certificado Auto-assinado

Quando `requireCertificate = false` e não há certificado carregado, o `ToolsFactory` gera automaticamente um certificado auto-assinado temporário usando OpenSSL. Este certificado:

- É válido por 1 dia
- Contém dados genéricos (CN: temp.fiscal-core.local)
- Satisfaz a API do NFePHP que exige um objeto `Certificate`
- **NÃO deve ser usado** para operações que requerem assinatura digital real
- É criado em memória e descartado após o uso

### Valores Dummy na Configuração

Quando não há certificado carregado, o `ConfigManager.getNFeConfig()` fornece:

- **CNPJ**: `00000000000000`
- **Razão Social**: `Empresa Temp`

Estes valores são usados apenas na configuração do NFePHP e não afetam operações de consulta.

## Segurança

⚠️ **IMPORTANTE**: O certificado auto-assinado gerado automaticamente:

- É adequado APENAS para operações de consulta/leitura
- NÃO deve ser usado em produção para emissão de documentos fiscais
- NÃO tem validade legal para assinatura de documentos
- É uma solução técnica para satisfazer requisitos da API NFePHP

Para operações de emissão, cancelamento e inutilização, você DEVE usar um certificado digital válido emitido por uma Autoridade Certificadora credenciada.

## Testes

Execute os testes relacionados a certificados opcionais:

```bash
vendor/bin/phpunit tests/OptionalCertificateTest.php
```

Todos os testes devem passar, validando que:
- Operações de consulta funcionam sem certificado
- Operações de emissão exigem certificado
- Adapters podem ser instanciados sem certificado

## Migração

Se você já usa o Fiscal Core, suas aplicações existentes continuarão funcionando sem alterações. A mudança é compatível com versões anteriores:

- Se você carrega o certificado no início, tudo funciona como antes
- Se você NÃO carrega o certificado, agora pode usar operações de consulta/status

## Troubleshooting

### Erro: "Certificado digital não carregado"

Este erro ocorre quando você tenta fazer uma operação que **requer** certificado (como emissão) sem ter carregado um certificado válido.

**Solução**: Carregue o certificado antes de chamar `emitir()`, `cancelar()` ou `inutilizar()`:

```php
$certManager = CertificateManager::getInstance();
$certManager->loadFromFile('/path/to/cert.pfx', 'senha');
```

### Erro de OpenSSL ao gerar certificado temporário

Se houver problemas ao gerar o certificado auto-assinado, verifique:

1. Extensão OpenSSL está habilitada no PHP: `php -m | grep openssl`
2. Permissões de escrita em `/tmp` (onde o arquivo de configuração temporário é criado)

## Veja Também

- [Configuração e Certificados](providers-and-config.md)
- [Exemplos de Uso](../examples/)
- [Testes](../tests/)
