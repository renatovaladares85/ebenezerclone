Você é responsável por implementar o plugin GLPI `permissioncore` para receber o domínio fora de clonagem removido do `ebenezerclone`.

Objetivo:
- Implementar serviço de autorização por perfil/entidade.
- Implementar enforcement backend de edição/bloqueio.
- Registrar logs de governança:
  - atores copiados;
  - falha de clonagem no chamado de origem.

Importante:
- O `ebenezerclone` mantém apenas `CLONE_CORE`.
- Não duplicar regras de clonagem no `permissioncore`.

Backlog obrigatório:
1. Estrutura do plugin `permissioncore` (setup/hook/inc/front/js/locales).
2. Serviço de autorização por perfil/entidade.
3. Matriz de permissões por perfil e permissões globais.
4. Endpoint frontend de permissões/locks.
5. Enforcement backend no fluxo de atualização de ticket.
6. Logging de governança (atores copiados e falha de clonagem na origem).

Restrições:
- Não alterar core GLPI.
- Não inventar requisitos fora do DRE.
- Um commit por mudança lógica.
