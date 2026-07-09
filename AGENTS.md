# Instruções do Projeto

## Versionamento GLPI por Commit

- Toda atualização de produto deve gerar commit.
- Todo commit de produto deve atualizar a versão do plugin GLPI no padrão `MAJOR.MINOR.PATCH`.
- Use `PATCH` para ajustes, correções e bug fixes.
- Use `MINOR` para implementação pontual, funcionalidade prevista ou melhoria compatível.
- Use `MAJOR` para mudança estrutural, arquitetura, layout amplo ou ruptura relevante.
- Antes de concluir o commit, atualize `setup.php`, `CHANGELOG.md` e, quando houver novo marco, `TRACEABILITY.md`.
- O `CHANGELOG.md` deve registrar cada commit no ledger da versão usando `<release>+git.<short_sha>` ou `<release>+git.<pending>` quando o hash ainda não existir.

## Referencias GLPI Locais

- Antes de implementar ou revisar comportamento dependente de padrao GLPI, consulte os MDs locais em `docs/local-glpi-standards/` quando existirem.
- Referencias esperadas:
  - `docs/local-glpi-standards/glpi-developer-standards.md`
  - `docs/local-glpi-standards/glpi-plugin-standards.md`
- Esses MDs sao gerados a partir dos PDFs oficiais locais de desenvolvimento GLPI e plugins GLPI.
- Os MDs sao artefatos locais para apoio do Codex e nao devem ser versionados.
