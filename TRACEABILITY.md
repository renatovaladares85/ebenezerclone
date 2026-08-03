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
- Release no `setup.php`: `3.1.28`
- Commit: `6848b3f2`
- Versão efetiva rastreável: `3.1.27+git.<short_sha>`

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

### 3.1.25 (patch release)
Criterio: correcoes compatíveis para GLPI 10.0.20 na clonagem de chamados.

Arvore logica de entrega:
- Compatibilidade GLPI 10.0.x
  - `82e9937` (metadata de compatibilidade e falha controlada de `Ticket::add()`)
- Conteudo clonado
  - `3f889fd` (normalizacao segura de `content` clonado para preservar aspas simples e HTML)

### 3.1.26 (patch release)
Criterio: documentacao operacional local para orientar Codex em padroes GLPI sem versionar artefatos gerados.

Arvore logica de entrega:
- Referencias locais GLPI
  - `0777f45` (MDs locais gerados a partir de PDFs oficiais e ignorados pelo Git)
- Governanca de uso
  - `0777f45` (AGENTS aponta para os guias locais)

### 3.1.27 (patch release)
Criterio: ajuste de governanca para manter referencias locais GLPI apenas nas instrucoes do agente.

Arvore logica de entrega:
- Governanca de uso
  - `HEAD` (remove referencia dos guias locais do README e mantem apontamento no AGENTS)

### 3.1.28 (patch release)
Criterio: correcao compatível de seguranca, LGPD, ACL e metadata GLPI 10.

Arvore logica de entrega:
- Compatibilidade e configuracoes globais
  - `HEAD` (faixa GLPI, migracao retrocompativel e regras independentes)
- Backend e minimizacao
  - `HEAD` (CSRF automatico no bootstrap, entidade/categoria, relacionados, logs e acao nativa)

### 3.1.29 (patch release)
Criterio: restauracao compativel do modo permissivo de clonagem, mantendo o modo estrito por permissao nativa.

Arvore logica de entrega:
- Modos de permissao na clonagem
  - `HEAD` (ACL do plugin obrigatoria, integridade da categoria e ACLs nativas condicionadas ao modo estrito)
## Matriz de rastreio

| Item | Identificador |
|---|---|
| DRE | `2601300202` |
| Branch principal de trabalho | `feature/2601300202-clonagem-chamados` |
| PRs relacionados | `#10`, `#11`, `#12` |
| Merges relevantes em `main` | `3d0af347`, `085b0e4a`, `354035a9` |
| Tags HML relacionadas | `v10.0.20-hml-003`, `v10.0.20-hml-004`, `v10.0.20-hml-005` |
| Versão atual do plugin | `3.1.29` |
| Regra de build por commit | `<release>+git.<short_sha>` |

## Regra operacional para próximos commits
Antes de concluir qualquer commit que altere comportamento, UI, i18n ou integração:
1. Gerar commit para toda atualização de produto.
2. Revisar o impacto de versionamento (`patch`, `minor`, `major`).
3. Atualizar `setup.php` com o novo SemVer quando a atualização mudar produto.
4. Usar `PATCH` para ajustes, correções e bug fixes.
5. Usar `MINOR` para implementação pontual, funcionalidade prevista ou melhoria compatível.
6. Usar `MAJOR` para mudança estrutural, arquitetura, layout amplo ou ruptura relevante.
7. Atualizar `CHANGELOG.md` com a entrada do commit no ledger da release.
8. Atualizar esta matriz (`TRACEABILITY.md`) quando houver novo marco/tag/PR.
