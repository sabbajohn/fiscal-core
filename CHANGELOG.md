# Changelog

## v1.1.1 - 2026-02-11

### Changed
- Atualizada configuracao do provider `nfse_nacional` para as rotas oficiais ADN/CNC por ambiente (`homologacao` e `producao`).
- Operacoes principais alinhadas ao padrao REST nacional:
  - `POST /nfse` (emissao)
  - `GET /nfse/{id}` (consulta)
  - `POST /nfse/{id}/eventos` (cancelamento por evento)
  - `POST /nfse` (substituicao via DPS)
- Base do ADN versionada para `.../api/v1` em homologacao e producao.
- `NacionalProvider` passou a suportar resolucao de endpoint por servico (`servico:/path`) e URL absoluta.
- `AbstractNFSeProvider::getNationalApiBaseUrl()` agora prioriza base por ambiente em `services.adn`.
- `NacionalCatalogService` passou a consumir endpoints configuraveis de catalogo com placeholders (`{codigo_municipio}`) e URL absoluta.
- Ajustes de compatibilidade para manter suporte a configuracoes legadas com caminhos relativos.

### Tests
- Incluidos testes unitarios para validacao da resolucao de rotas por servico no provider e no catalogo.

### Notes
- A execucao local de testes permanece bloqueada neste ambiente por erro de runtime do PHP:
  - `Library not loaded: /opt/homebrew/opt/net-snmp/lib/libnetsnmp.40.dylib`

## v1.1.0 - 2026-02-11

### Added
- Provider NFSe nacional com operações completas:
  - emissão (`emitir`)
  - consulta (`consultar`)
  - cancelamento (`cancelar`)
  - substituição (`substituir`)
  - consulta por RPS/lote
  - download XML/DANFSe
- Serviço de catálogo nacional com cache local e fallback stale:
  - municípios nacionais
  - alíquotas por código IBGE
- Normalização central de retorno SEFAZ para JSON em `ResponseHandler`:
  - `parseSefazRetorno()`
  - `parseSefazRetornoAsJson()`
- Verificação de prontidão de homologação em `NFSeFacade`:
  - `verificarProntidaoHomologacao()`

### Changed
- Arquitetura NFSe consolidada para modo nacional-only:
  - resolução interna sempre para `nfse_nacional`
  - parâmetro `municipio` mantido por compatibilidade e marcado como deprecado (metadata/warnings)
- `ProviderRegistry` com suporte a fallback nacional e aliases legados (`alias_of`)
- `NFSeAdapter` e `NFSeFacade` com metadata explícita:
  - `provider_key`
  - `municipio_ignored`
  - `warnings`
- `NacionalProvider` reforçado para homologação:
  - geração de XML mais aderente ao padrão de integração
  - assinatura XML opcional/obrigatória por configuração (`signature_mode`)
  - parser robusto para retornos `CompNfse/InfNfse` com namespace

### Config
- `config/nfse-municipios.json` consolidado com bloco principal `nfse_nacional`
- aliases legados (`curitiba`, `joinville`, etc.) apontando para `nfse_nacional`
- novas chaves de config para homologação:
  - `xml_namespace`
  - `signature_mode`
  - `backend`

### Notes
- Validação local de testes não executada neste ambiente por falha de runtime do PHP:
  - `Library not loaded: /opt/homebrew/opt/net-snmp/lib/libnetsnmp.40.dylib`

## v1.0.3 - 2026-02-11

### Changed
- Evolução do fluxo de montagem/validação de notas com melhorias em `NotaFiscal` e `NotaFiscalBuilder`.
- Ajustes no `IdentificacaoDTO` para suportar novos campos de identificação.
- Atualizações nos nós de documento fiscal:
  - `EmitenteNode`
  - `DestinatarioNode`
  - `PagamentoNode`
- Melhorias de validação e cobertura de campos adicionais no processo de composição da NFe/NFCe.

## v1.0.2 - 2026-02-11

### Changed
- Alinhado `NFeAdapter` e `NFCeAdapter` às assinaturas atuais do `nfephp-org/sped-nfe` (`v5.2.5`).
- Atualizado retorno de `cancelar` e `inutilizar` para `string` (XML da SEFAZ) no contrato `NotaFiscalInterface`.
- Corrigida chamada de `sefazInutiliza` para o formato atual: `(serie, numeroInicial, numeroFinal, justificativa, tpAmb, ano)`.
- Ajustado `NFeFacade` e `NFCeFacade` para:
  - Retornar `xml_response` nas operações de cancelamento/inutilização.
  - Expor `cstat` extraído do XML.
  - Calcular flags `cancelado`/`inutilizado` a partir de `cStat` de sucesso.

### Dependency updates
- `nfephp-org/sped-nfe`: `v5.2.3` -> `v5.2.5`
- `phpunit/phpunit`: `10.5.60` -> `10.5.63`
- `sebastian/comparator`: `5.0.4` -> `5.0.5`

### Notes
- A execução local de testes não foi possível no ambiente atual devido a erro de runtime do PHP:
  - `Library not loaded: /opt/homebrew/opt/net-snmp/lib/libnetsnmp.40.dylib`
