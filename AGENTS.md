# Instruções do Projeto

## Versionamento GLPI por Commit

- Toda atualização de produto deve gerar commit.
- Todo commit de produto deve atualizar a versão do plugin GLPI no padrão `MAJOR.MINOR.PATCH`.
- Use `PATCH` para ajustes, correções e bug fixes.
- Use `MINOR` para implementação pontual, funcionalidade prevista ou melhoria compatível.
- Use `MAJOR` para mudança estrutural, arquitetura, layout amplo ou ruptura relevante.
- Antes de concluir o commit, atualize `setup.php`, `CHANGELOG.md` e, quando houver novo marco, `TRACEABILITY.md`.
- O `CHANGELOG.md` deve registrar cada commit no ledger da versão usando `<release>+git.<short_sha>` ou `<release>+git.<pending>` quando o hash ainda não existir.
