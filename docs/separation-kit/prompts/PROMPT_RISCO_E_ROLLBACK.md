Produza plano de rollout seguro para separação `CLONE_CORE` x `PERMISSION_CORE`.

Cobertura obrigatória:
1. Riscos técnicos por etapa.
2. Dependências e ordem de ativação.
3. Critérios de go/no-go.
4. Rollback por etapa (config, enforcement, endpoint, observabilidade).
5. Risco de descompasso entre:
   - logs de clonagem (`ebenezerclone`),
   - logs de governança (`permissioncore`).

Premissas:
- `ebenezerclone` permanece focado em clonagem.
- `permissioncore` recebe governança de permissão/edição.
- Fallback seguro quando `permissioncore` estiver inativo.
