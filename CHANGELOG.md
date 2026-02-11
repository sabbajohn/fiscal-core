# Changelog

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
