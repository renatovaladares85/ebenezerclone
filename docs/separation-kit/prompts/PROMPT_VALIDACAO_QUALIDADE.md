Valide a entrega em duas matrizes independentes.

Matriz A - Não regressão `CLONE_CORE` (`ebenezerclone`):
1. Clone criado com status atribuído quando habilitado.
2. Não cópia de chamados relacionados quando política exigir.
3. Cópia de vínculos de documentos/anexos.
4. Vínculo origem-clone criado.
5. Atividades informativas criadas quando habilitadas.
6. Logs de timeline de clonagem esperados presentes.

Matriz B - `PERMISSION_CORE` (novo plugin):
1. Permissões globais aplicadas.
2. Matriz por perfil aplicada.
3. Enforcement backend ativo (sem bypass por payload).
4. Logs de governança gerados:
   - atores copiados;
   - falha de clonagem no chamado de origem.

Checklist transversal:
- Sem texto novo em inglês no escopo UI alterado.
- Sem mojibake.
- `msgfmt` válido para locales alterados.
