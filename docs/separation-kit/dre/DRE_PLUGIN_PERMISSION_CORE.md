# DRE - Plugin Permission Core (Proposto)

## 1. Contexto
Separar governança de permissões/edição do `ebenezerclone` para um novo plugin dedicado (`PERMISSION_CORE`), mantendo o `ebenezerclone` focado em clonagem (`CLONE_CORE`).

## 2. Fronteira de responsabilidade

### 2.1 Permanece no `ebenezerclone` (CLONE_CORE)
- Forçar status do chamado clonado para **Atribuído**.
- Não copiar chamados relacionados.
- Copiar vínculos de documentos/anexos para o chamado clonado.
- Vincular chamado de origem e chamado clonado.
- Criar atividades informativas durante a clonagem.
- Logs de histórico de timeline:
  - Registrar criação do clone no chamado clonado.
  - Registrar referência do clone no chamado de origem.
  - Registrar criação do vínculo entre chamados.
  - Registrar acompanhamentos informativos criados pelo plugin.

### 2.2 Vai para o novo plugin (`PERMISSION_CORE`)
- Permissões globais.
- Matriz de permissões por perfil.
- Registrar atores copiados.
- Registrar falha de clonagem no chamado de origem.

## 3. Objetivo do novo plugin
- Centralizar autorização por perfil/entidade e regras de edição/bloqueio.
- Expor contrato de decisão para consumo por outros plugins.
- Aplicar enforcement backend (não depender de lock visual).

## 4. Escopo funcional (MVP)
- Serviço de autorização por perfil/entidade.
- Hook backend de enforcement em atualizações de ticket.
- Endpoint frontend para estado de permissões/locks.
- Camada de logs de governança, incluindo:
  - log de atores copiados;
  - log de falha de clonagem no chamado de origem.

## 5. Critérios de aceite
- `ebenezerclone` não exibe nem persiste configuração de permissões.
- Regras de clonagem do `CLONE_CORE` permanecem funcionais sem regressão.
- Novo plugin concentra governança de permissão e logs de governança.

## 6. Estratégia de teste
- Matriz de autorização por perfil/entidade no novo plugin.
- Cenários de edição bloqueada/liberada validados no backend.
- Logs de governança gerados conforme regra.
- Regressão de clonagem validada no `ebenezerclone`.

## 7. Riscos
- Descompasso de responsabilidade de logs entre plugins.
- Migração incompleta de governança deixando comportamento duplicado.
- Falta de enforcement backend no novo plugin.
