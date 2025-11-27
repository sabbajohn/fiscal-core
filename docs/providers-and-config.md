# 📄 Documentação: Providers e Configuração Externa na NFSe

## Objetivo
Descrever como utilizar **Providers** para lidar com as particularidades municipais da NFSe e como aplicar **configuração externa** para evitar duplicação de código quando diferentes municípios compartilham o mesmo padrão.

---

## Providers

### O que são
- **Providers** são implementações específicas para cada município ou sistema de NFSe.
- Encapsulam diferenças como:
  - Estrutura XML
  - URLs de webservice
  - Formato de alíquota (ex.: `2` vs `0.02`)
  - Versão do layout ABRASF

### Como funcionam
- A biblioteca define uma interface genérica (`NotaServicoInterface`).
- Cada Provider implementa essa interface conforme as regras do município.
- O Adapter NFSe delega ao Provider correto.

Exemplo:
```php
$provider = new JoinvilleProvider($config);
$nfseService = new NFSeService($provider);

$nfseService->emitirNota($dados);
```

---

## Configuração Externa

### Problema
Alguns municípios são **idênticos** em implementação (ex.: Curitiba e Campo Largo).
Duplicar código seria inviável e aumentaria a manutenção.

### Solução
Utilizar **configuração externa** para parametrizar os Providers.
Assim, municípios que compartilham lógica usam o mesmo Provider genérico, apenas com configurações diferentes.

---

### Estrutura de Configuração (JSON)
```json
{
  "curitiba": {
    "provider": "AbrasfV2Provider",
    "wsdl": "https://nfse.curitiba.pr.gov.br/ws/nfse.asmx?wsdl",
    "aliquota_format": "decimal",
    "versao": "2.02"
  },
  "campo_largo": {
    "provider": "AbrasfV2Provider",
    "wsdl": "https://nfse.campolargo.pr.gov.br/ws/nfse.asmx?wsdl",
    "aliquota_format": "decimal",
    "versao": "2.02"
  },
  "joinville": {
    "provider": "JoinvilleProvider",
    "wsdl": "https://nfse.joinville.sc.gov.br/ws/nfse.asmx?wsdl",
    "aliquota_format": "percentual",
    "versao": "2.01"
  }
}
```

---

### Registry de Providers
Um **Registry** centraliza o carregamento das configurações e instancia o Provider correto.

```php
class ProviderRegistry {
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function get(string $municipio): ProviderInterface {
        $conf = $this->config[$municipio];
        $providerClass = $conf['provider'];
        return new $providerClass($conf);
    }
}
```

Uso:
```php
$registry = new ProviderRegistry($configJson);
$provider = $registry->get('curitiba');
```

---

## Benefícios
- **Zero duplicação**: municípios idênticos compartilham o mesmo Provider.
- **Flexibilidade**: mudanças de URL ou versão são feitas apenas em configuração.
- **Escalabilidade**: adicionar novos municípios exige apenas incluir no arquivo de configuração.
- **Separação de responsabilidades**: lógica no código, particularidades nos arquivos de configuração.

---

## Conclusão
- Use **herança** quando houver diferenças reais de lógica.
- Use **registry + configuração externa** quando a diferença for apenas de parâmetros.
- Essa abordagem garante uma biblioteca **limpa, flexível e fácil de manter**, preparada para lidar com dezenas de municípios sem duplicação de código.

---

👉 Dica: se quiser, posso consolidar este conteúdo com as seções de DTOs e XmlBuilder em um README arquitetural completo.
