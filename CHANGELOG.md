# Changelog - Ebenezer Clone

Este arquivo segue versionamento semântico (`MAJOR.MINOR.PATCH`) e registra
as alterações publicadas para rastreabilidade técnica e operacional.

Regra de rastreio por commit (obrigatória):
- Cada commit deve aparecer no changelog.
- Para manter aderência ao SemVer do GLPI, o número em `setup.php` representa a release.
- Cada commit é identificado como build da release usando:
  - `VERSAO_EFETIVA = <semver_release>+git.<short_sha>`
  - Exemplo: `1.0.1+git.6848b3f2`

## [2.2.2] - 2026-04-24

### Bugfix release
- Restaura a regra de negócio que cria chamados clonados com status `Atribuído`.
- Move o controle dessa regra para configuração explícita do plugin, desacoplando o status da política genérica de cópia do campo `status`.
- Mantém a política de edição do formulário separada das regras de negócio do fluxo de clonagem.

### Ledger de commits da versão (100% rastreável)
- `2.2.2+git.<pending>` - Restaura status atribuído na clonagem e adiciona toggle de configuração
- `2.2.2+git.<pending>` - Ajusta UX da remoção de perfil e rótulos da matriz de bloqueio

## [2.2.3] - 2026-04-29

### Bugfix release
- Faz o bloqueio visual das propriedades deixar de atuar quando o chamado estiver em status final, incluindo `Solucionado` e `Fechado`.
- Padroniza o lock visual das propriedades para aplicar o mesmo comportamento de `disabled` e classes visuais nos campos bloqueados, incluindo a data de abertura.

### Ledger de commits da versão (100% rastreável)
- `2.2.3+git.<pending>` - Respeita status final no bloqueio visual
- `2.2.3+git.<pending>` - Padroniza lock visual das propriedades

## [2.0.0] - 2026-04-17

### Major release
- Consolidates permission matrix behavior for clone operations, assignment controls and ticket property policies.
- Finalizes i18n coverage in configuration screens and updates locale artifacts.
- Promotes plugin release line to 2.0.0.

### Ledger de commits da versao (100% rastreavel)
- 2.0.0+git.<pending> - Bump de versao para 2.0.0 e alinhamento de documentacao de release
## [1.0.5] - 2026-04-15

### Internacionalização e UX de configuração (PATCH)
- Atualiza catálogo de traduções para novas permissões e mensagens de apoio.
- Recompila arquivo `pt_BR.mo` a partir de `pt_BR.po`.
- Mantém rastreabilidade de publicação incremental por commit.

### Ledger de commits da versão (100% rastreável)
- `1.0.5+git.<pending>` - Atualiza traduções e recompila locale do plugin

## [1.0.4] - 2026-04-15

### Enforcement de permissões por perfil (PATCH)
- Aplica permissão por perfil para clonagem de chamado na aba e no submit.
- Aplica controle de permissão por perfil para edição de atribuição (grupo/técnico) com fallback legado.
- Integra permissão de ação de clonagem em massa no frontend via endpoint de permissões.

### Ledger de commits da versão (100% rastreável)
- `1.0.4+git.<pending>` - Aplica regras por perfil no fluxo de chamados

## [1.0.3] - 2026-04-15

### PermissÃµes por perfil (PATCH)
- Adiciona base de matriz de permissÃµes por perfil nas configuraÃ§Ãµes do plugin.
- Inclui catÃ¡logo de permissÃµes com suporte a tooltip funcional por opÃ§Ã£o.
- MantÃ©m fallback para comportamento legado quando nÃ£o houver configuraÃ§Ã£o de perfil.

### Ledger de commits da versÃ£o (100% rastreÃ¡vel)
- `1.0.3+git.<pending>` - Estrutura matriz de permissÃµes por perfil no config do plugin

## [1.0.2] - 2026-04-15

### Rastreabilidade e governança (PATCH)
- Formaliza rastreabilidade por commit no versionamento da release ativa.
- Consolida matriz de rastreio com regra `<release>+git.<short_sha>`.
- Atualiza instruções do agente para obrigar revisão/sugestão de versionamento a cada commit.
- Publica nova release semântica para refletir as publicações após `1.0.1`.

### Ledger de commits da versão (100% rastreável)
- `1.0.2+git.3f5dc7b8` - Adiciona rastreabilidade e hierarquia de versionamento
- `1.0.2+git.ca3b1ebc` - Formaliza versionamento por commit na rastreabilidade
- `1.0.2+git.41f3a324` - Atualiza instruções do agente para versionamento por commit

## [1.0.1] - 2026-04-15

### Correções (PATCH)
- Corrige perda intermitente de categoria na clonagem ao alterar tipo.
- Impede fallback indevido para categoria original quando categoria não é selecionada.
- Copia vínculos de anexos (`Document_Item`) do ticket origem para o ticket clonado.
- Restringe locks de UI do plugin ao contexto de chamados (Ticket), evitando impacto em `ITILCategory` (campo "Filho de").
- Ajusta e completa traduções do plugin (`pt_BR.po`/`pt_BR.mo`).

### Ledger de commits da versão (100% rastreável)
- `1.0.1+git.01565164` - Corrige perda intermitente de categoria na clonagem de chamado
- `1.0.1+git.772222af` - Copia vínculos de anexos ao clonar chamado
- `1.0.1+git.6f1bf44d` - Ajusta traduções do plugin e recompila locale pt_BR
- `1.0.1+git.6848b3f2` - Restringe locks JS do plugin ao contexto de ticket
- `1.0.1+git.d70a6cd2` - Atualiza versão do plugin para 1.0.1
- `1.0.1+git.3f5dc7b8` - Adiciona rastreabilidade e hierarquia de versionamento

### Contexto de release (repositório)
- PR: `#10`, `#11`, `#12` (incrementais do DRE)
- Tags de homologação correlatas:
  - `v10.0.20-hml-003`
  - `v10.0.20-hml-004`
  - `v10.0.20-hml-005`

## [1.0.0] - Base inicial

### Marco inicial
- Versão inicial do plugin `ebenezerclone`.

## [1.0.5] - complemento de governanca de permissoes

### Politica de propriedades (PATCH)
- Mantem hardcode minimo para excecoes criticas do core (`canRequesterUpdateItem`, `CHANGEPRIORITY`); `status` passa a ser controlado somente pelo plugin.
- Reforca uso da matriz `Bloquear` / `Habilitar` / `Ignorar` como controle principal por perfil.
- Registra logs de decisao para bloqueio por plugin e override por core.

### Ledger de commits da versao (100% rastreavel)
- `1.0.5+git.<pending>` - Ajusta precedencia core x plugin para propriedades e documenta politica operacional
- `1.0.5+git.<pending>` - Adiciona opcao global para liberar edicao de categoria quando vazia
- `1.0.5+git.<pending>` - Corrige i18n da opcao de categoria vazia (pt_BR) e define default de politicas de propriedades como Bloquear com migracao retroativa
- `1.0.5+git.<pending>` - Corrige textos com mojibake na secao de logs e adiciona checklist anti-mojibake no README
