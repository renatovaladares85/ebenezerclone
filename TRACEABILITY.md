# Rastreabilidade de Versão e Commits - Ebenezer Clone

## Objetivo
Consolidar rastreabilidade entre:
- DRE/chamado
- commits técnicos
- PRs
- tags de homologação
- versão semântica do plugin

## Política de versionamento por commit (GLPI + SemVer)

Para aderir à documentação de desenvolvimento/plugins do GLPI e ainda representar
cada commit no versionamento:

1. `setup.php` mantém a versão de release semântica (`MAJOR.MINOR.PATCH`).
2. Cada commit deve ser registrado com uma versão efetiva de build:
   - `<release>+git.<short_sha>`
3. O changelog deve conter um ledger com todos os commits da release ativa.
4. Quando houver nova release (novo SemVer), iniciar novo bloco de ledger.

Exemplo:
- Release no `setup.php`: `2.0.0`
- Commit: `6848b3f2`
- Versão efetiva rastreável: `2.0.0+git.<short_sha>`

## DRE principal
- `2601300202` - Clonagem de chamados

## Hierarquia de versionamento (SemVer)

### 1.0.0 (base)
- Publicação inicial do plugin.

### 1.0.1 (patch release)
Critério: correções sem quebra de compatibilidade.

Árvore lógica de entrega:
- Correções de clonagem
  - `01565164` (categoria/tipo)
  - `772222af` (anexos)
- Correções de i18n
  - `6f1bf44d` (traduções e locale compilado)
- Correção de escopo UI (somente Ticket)
  - `6848b3f2` (locks JS isolados para chamados)
- Publicação de versão
  - `d70a6cd2` (bump para 1.0.1)

### 1.0.2 (patch release)
Critério: atualização de release para refletir publicações posteriores.

Árvore lógica de entrega:
- Rastreabilidade/versionamento por commit
  - `3f5dc7b8` (changelog + matriz de rastreio)
  - `ca3b1ebc` (política por commit)
  - `41f3a324` (instruções do agente)
- Publicação de versão
  - `HEAD` (bump para 1.0.2)

### 1.0.3 (patch release)
Critério: introdução da base de permissões por perfil no plugin.

Árvore lógica de entrega:
- Configuração centralizada por perfil
  - `HEAD` (estrutura de matriz de permissões, tooltips e persistência)

### 1.0.4 (patch release)
Critério: aplicação das permissões por perfil no fluxo de chamados.

Árvore lógica de entrega:
- Enforcement em contexto de Ticket
  - `HEAD` (clone por perfil, atribuição por perfil e ação massiva)

### 1.0.5 (patch release)
Critério: atualização de i18n para novas permissões e orientações de tela.

Árvore lógica de entrega:
- Traduções e locale compilado
  - `HEAD` (novos textos, tooltips e compilação do `pt_BR.mo`)

### 2.0.0 (major release)
Criterio: consolidacao de mudancas acumuladas de permissao e i18n com promocao de linha semantica.

Arvore logica de entrega:
- Consolidacao de release
  - HEAD (bump para 2.0.0 e alinhamento de artefatos de release)
## Matriz de rastreio

| Item | Identificador |
|---|---|
| DRE | `2601300202` |
| Branch principal de trabalho | `feature/2601300202-clonagem-chamados` |
| PRs relacionados | `#10`, `#11`, `#12` |
| Merges relevantes em `main` | `3d0af347`, `085b0e4a`, `354035a9` |
| Tags HML relacionadas | `v10.0.20-hml-003`, `v10.0.20-hml-004`, `v10.0.20-hml-005` |
| Versão atual do plugin | `2.0.0` |
| Regra de build por commit | `<release>+git.<short_sha>` |

## Regra operacional para próximos commits
Antes de concluir qualquer commit que altere comportamento, UI, i18n ou integração:
1. Revisar se há impacto de versionamento (`patch`, `minor`, `major`).
2. Atualizar `setup.php` quando houver nova release.
3. Atualizar `CHANGELOG.md` com a entrada do commit no ledger da release.
4. Atualizar esta matriz (`TRACEABILITY.md`) quando houver novo marco/tag/PR.
