# Changelog - Ebenezer Clone

Este arquivo segue versionamento semântico (`MAJOR.MINOR.PATCH`) e registra
as alterações publicadas para rastreabilidade técnica e operacional.

Regra de rastreio por commit (obrigatória):
- Cada commit deve aparecer no changelog.
- Para manter aderência ao SemVer do GLPI, o número em `setup.php` representa a release.
- Cada commit é identificado como build da release usando:
  - `VERSAO_EFETIVA = <semver_release>+git.<short_sha>`
  - Exemplo: `1.0.1+git.6848b3f2`

## [3.1.29] - 2026-08-03

### Patch release
- Restaura o modo permissivo: com permissões nativas desmarcadas, mantém validações de integridade e autorização do plugin, sem exigir ACLs nativas de categoria, entidade ou criação antes da clonagem.
- No modo estrito, aplica essas ACLs nativas antes da criação e impede vínculos parciais quando alguma delas falhar.
- Renomeia a opção para refletir que ela controla permissões nativas de categoria, entidade e criação, preservando a chave persistida e os defaults de instalação/upgrade.

### Ledger de commits da versão (100% rastreável)
- `3.1.29+git.a8e9ceb` - avoid duplicate CSRF validation in clone endpoint
- `3.1.29+git.d7239e7` - restore permissive clone authorization mode
- `3.1.29+git.13e0728` - merge PR #5 into main

## [3.1.28] - 2026-08-03

### Patch release
- Restringe a compatibilidade declarada a GLPI `>= 10.0.20` e `< 11.0.0`.
- Adiciona configurações globais independentes para exibição adicional de relacionados e exigência do direito nativo de criação de Ticket.
- Preserva o comportamento efetivo de upgrades: exibição adicional ligada e direito nativo dispensado quando as novas chaves ainda não existirem.
- Mantém a validação CSRF automática do GLPI e remove a segunda validação do token de uso único no endpoint de clonagem, permitindo que a rotina de clonagem seja alcançada sem reduzir a proteção.
- Exige POST, CSRF, leitura da origem, categoria visível/compatível, entidade final acessível e, quando configurado, criação nativa no backend.
- Reduz exposição do endpoint de relacionados e remove dependência do perfil `Super-Admin`.
- Minimiza logs e mensagens de falha, sem conteúdo livre, títulos ou detalhes internos.

### Ledger de commits da versão (100% rastreável)
- `3.1.28+git.04cbbe5` - harden clone ACL, CSRF, LGPD logging and GLPI 10 metadata
- `3.1.28+git.b2ab8ad` - make CI validation dependencies and package version parsing explicit

## [3.1.27] - 2026-07-09

### Patch release
- Remove do `README.md` a referencia aos guias locais GLPI nao versionados.
- Mantem o apontamento desses guias exclusivamente no `AGENTS.md`, como regra operacional para o Codex.

### Ledger de commits da versão (100% rastreável)
- `3.1.27+git.<pending>` - keep local GLPI standards references only in AGENTS

## [3.1.26] - 2026-07-09

### Patch release
- Adiciona referencias locais nao versionadas para Codex consultar padroes de desenvolvimento GLPI e plugins GLPI.
- Protege os MDs gerados via `.gitignore`.
- Formaliza no `AGENTS.md` o uso desses guias antes de alteracoes dependentes de padrao GLPI.

### Ledger de commits da versão (100% rastreável)
- `3.1.26+git.0777f45` - add local GLPI standards references for Codex

## [3.1.25] - 2026-07-09

### Patch release
- Ajusta a metadata de compatibilidade GLPI para a linha `10.0.x` usando `requirements['glpi']` como fonte principal, sem `minGlpiVersion` legado.
- Corrige o tratamento de falha em `Ticket::add()` para não chamar `Ticket::getErrors()` diretamente no GLPI 10.0.20.
- Normaliza o `content` clonado pelo helper oficial `Glpi\Toolbox\Sanitizer` antes de `Ticket::add()`, preservando aspas simples, apostrofos e HTML esperado.
- Mantém a correção restrita à observabilidade e ao erro controlado de clonagem, sem alterar regras de negócio ou campos copiados.
- Alinha a rastreabilidade operacional da versão `3.1.25` e formaliza a política de bump SemVer por commit.

### Ledger de commits da versão (100% rastreável)
- `3.1.25+git.82e9937` - fix clone add failure handling and GLPI 10.0 metadata
- `3.1.25+git.3f889fd` - normalize cloned ticket content before Ticket::add to preserve quotes and HTML
- `3.1.25+git.1b40c07` - align 3.1.25 traceability and local versioning rules

## [3.1.24] - 2026-06-02

### Minor release
- Adiciona opção global `Copiar itens relacionados (dispositivos)` para copiar os itens da aba Itens durante a clonagem.
- Copia vínculos `Item_Ticket` via API nativa do GLPI, preservando validações, hooks, histórico e atualizações relacionadas.
- Mantém a opção existente de chamados relacionados separada da nova cópia de itens/dispositivos.

### Ledger de commits da versão (100% rastreável)
- `3.1.24+git.<pending>` - copy related ticket items during clone using native GLPI relation API

## [3.0.24] - 2026-05-29

### Patch release
- Preserva a descrição original do chamado origem após a criação do clone, evitando nova transformação pelo preparo interno do GLPI.
- Remove dependência de `_rawcontent` e regrava diretamente o campo `content` do chamado destino com o valor original.

### Ledger de commits da versão (100% rastreável)
- `3.0.24+git.<pending>` - preserve original cloned ticket description after ticket creation

## [3.0.23] - 2026-05-29

### Patch release
- Corrige a persistência da descrição clonada para preservar quebras de linha e evitar `n` solto no conteúdo do chamado destino.
- Mantém a correção restrita ao fluxo do plugin, sem alteração de core GLPI, UI, ACL ou regras de negócio da clonagem.

### Ledger de commits da versão (100% rastreável)
- `3.0.23+git.ea47614e` - preserve cloned ticket description raw content to avoid loose newline markers
- `3.0.23+git.<pending>` - bump version to 3.0.23 and update changelog

## [3.0.21] - 2026-05-22

### Patch release
- Corrige precedência de leitura do formulário de clonagem para priorizar os campos visíveis (`título`, `tipo` e `categoria`) sobre os campos ocultos.
- Garante envio explícito de `name`, `type` e `itilcategories_id` no payload final do clone para respeitar os valores definidos no formulário.
- Amplia validação pós-clone para detectar divergência de `título`, `tipo` e `categoria` no chamado gerado.

### Ledger de commits da versão (100% rastreável)
- `3.0.21+git.3cbaa2c9` - respeita título/tipo/categoria do formulário no clone e amplia validação pós-clone
- `3.0.21+git.<pending>` - bump de versão para 3.0.21 e atualização do changelog

## [3.0.20] - 2026-05-22

### Patch release
- Corrige recarga da categoria no formulário de clonagem para manter sincronia com o último tipo selecionado (Incidente/Requisição).
- Ao trocar o tipo no clone, a categoria passa a ser sempre limpa e recarregada pelo endpoint nativo com filtros corretos do tipo.
- Elimina condição de corrida em trocas rápidas de tipo, garantindo prevalência da última seleção do usuário.

### Ledger de commits da versão (100% rastreável)
- `3.0.20+git.425a694a` - reload de categoria por último tipo selecionado no clone
- `3.0.20+git.<pending>` - bump de versão para 3.0.20 e atualização do changelog

## [3.0.19] - 2026-05-22

### Patch release
- Remove definitivamente alteração de core para restrição de clonagem nativa de chamados.
- Restringe a ação nativa `Clone/Clonar` para perfil com nome exatamente `Super-Admin`, ocultando a opção no menu de ações do chamado e nas ações em massa para demais perfis.
- Adiciona barreira no backend via hook `PRE_ITEM_ADD` do plugin para bloquear tentativa de clonagem nativa por POST fora do perfil `Super-Admin`.

### Ledger de commits da versão (100% rastreável)
- `3.0.19+git.790114c0` - revert core clone restriction change to honor no-core-modification rule
- `3.0.19+git.20f2a2e1` - enforce native clone restriction in plugin UI and backend for non-Super-Admin profiles

## [3.0.15] - 2026-05-20

## [3.0.18] - 2026-05-20

### Patch release
- Adiciona regra global `recalculate_title_from_category` (default desligado) para controlar se o título do clone sempre deriva da categoria selecionada.
- Corrige recalculo de título no formulário de clonagem após troca de categoria e após troca de tipo com recarga da categoria.
- Garante no backend que, com regra ligada, o título final sempre é derivado da categoria final; com regra desligada, mantém o título original.
- Corrige `Copy related tickets` para copiar vínculos `Ticket_Ticket` do chamado origem, separado da regra de vínculo origem-clone.
- Ajusta textos/logs para refletir cópia de vínculos de chamados relacionados.

### Ledger de commits da versão (100% rastreável)
- `3.0.18+git.<pending>` - add global title recalculation toggle and fix related ticket link copy behavior

### Patch release
- Corrige persistência da seção de autorização de clonagem eliminando falso-positivo de payload inválido no salvar.
- Adiciona parsing resiliente para payload de autorização (JSON direto, HTML-encoded, URL-encoded e base64 legado).
- Inicializa o campo oculto de autorização com payload JSON válido gerado no backend para evitar falha quando o JS não sincroniza.

### Ledger de commits da versão (100% rastreável)
- `3.0.15+git.<pending>` - fix clone authorization payload decoding and default hidden scope serialization

## [3.0.14] - 2026-05-20

### Patch release
- Remove superfícies legadas de governança de permissão em `config.class.php` e `clone.class.php`, mantendo apenas autorização de clonagem por `perfil + entidade + recursivo`.
- Corrige persistência da autorização: payload JSON vazio agora é válido e não dispara erro falso de payload inválido.
- Remove linha placeholder vazia da lista de autorizações e mantém ordenação estável por nome de perfil no locale da sessão.
- Mantém compatibilidade de leitura de payload legado em base64 para evitar quebra em sessões antigas.

### Ledger de commits da versão (100% rastreável)
- `3.0.14+git.<pending>` - remove legacy permission governance and harden clone authorization persistence

## [3.0.13] - 2026-05-20

### Patch release
- Ignora `group_toggles` legado para permissões `profile_only`, evitando bloqueio indevido da clonagem quando a matriz de autorização está correta.

### Ledger de commits da versão (100% rastreável)
- `3.0.13+git.<pending>` - avoid legacy group toggle blocking profile-only clone permission

## [3.0.12] - 2026-05-20

### Patch release
- Remove bloqueio de visibilidade da aba de clonagem pelo rightname legado da classe, mantendo controle apenas pela autorização configurada no plugin.

### Ledger de commits da versão (100% rastreável)
- `3.0.12+git.<pending>` - decouple clone tab visibility from legacy profile right

## [3.0.11] - 2026-05-20

### Patch release
- Remove dependência de `Ticket::canCreate()` para visibilidade/execução da clonagem, mantendo como fonte única a autorização configurada no plugin.

### Ledger de commits da versão (100% rastreável)
- `3.0.11+git.<pending>` - allow clone based on plugin authorization only

## [3.0.10] - 2026-05-20

### Patch release
- Refatora UI de autorização para fluxo de adição no topo + lista persistida abaixo, com remoção por item.
- Remove linhas vazias após reload e mantém apenas controles de adicionar autorização.

### Ledger de commits da versão (100% rastreável)
- `3.0.10+git.<pending>` - refactor clone authorization add/list workflow

## [3.0.9] - 2026-05-20

### Patch release
- Corrige persistência da autorização de clonagem ao salvar, aceitando payload em formatos resilientes no backend.
- Ajusta serialização do payload no frontend para envio estável (base64 JSON).

### Ledger de commits da versão (100% rastreável)
- `3.0.9+git.<pending>` - fix clone authorization payload persistence on save

## [3.0.8] - 2026-05-20

### Patch release
- Corrige ordenação dos perfis nas linhas adicionadas dinamicamente, preservando a mesma ordem exibida na primeira linha.

### Ledger de commits da versão (100% rastreável)
- `3.0.8+git.<pending>` - fix dynamic profile dropdown ordering

## [3.0.7] - 2026-05-20

### Patch release
- Ordena a lista de perfis por nome usando locale da sessão do usuário (`glpilanguage`) na tela de configuração.

### Ledger de commits da versão (100% rastreável)
- `3.0.7+git.<pending>` - sort profile list by session locale in config UI

## [3.0.6] - 2026-05-20

### Patch release
- Move validação obrigatória de perfil para o clique em "Adicionar autorização" (sem alerta no salvar).
- Substitui rótulo genérico de entidade por nome da entidade raiz para a opção `0` na grade de autorização.

### Ledger de commits da versão (100% rastreável)
- `3.0.6+git.<pending>` - fix add-row profile validation timing and root entity label

## [3.0.5] - 2026-05-20

### Patch release
- Corrige validação de perfil para não disparar alerta ao abrir a tela de configuração sem interação do usuário.
- Mantém validação apenas no submit e ignora linha padrão vazia.

### Ledger de commits da versão (100% rastreável)
- `3.0.5+git.<pending>` - fix early profile validation alert on config load

## [3.0.4] - 2026-05-20

### Patch release
- Corrige layout e comportamento da grade de autorização de clonagem para manter linhas consistentes.
- Corrige dropdown de entidade na primeira linha e impede salvar linhas sem perfil selecionado.

### Ledger de commits da versão (100% rastreável)
- `3.0.4+git.<pending>` - fix clone authorization grid consistency and profile-required validation

## [3.0.3] - 2026-05-20

### Patch release
- Corrige persistência da autorização de clonagem (perfil+entidade+recursivo) ao salvar configurações.
- Alinha controles da seção de autorização com componentes nativos de dropdown do GLPI para perfil, entidade e recursividade.
- Adiciona proteção backend para preservar a configuração anterior em caso de payload inválido.

### Ledger de commits da versão (100% rastreável)
- `3.0.3+git.<pending>` - fix clone authorization config persistence and native dropdown behavior

## [3.0.2] - 2026-05-20

### Patch release
- Move autorização de clonagem para configuração explícita do plugin com lista de Perfil + Entidade + Recursivo.
- Aplica a autorização configurada no runtime da clonagem (aba e ações), sem bypass para super-admin.
- Adiciona script de validação para executar `php -l` e `msgfmt` com fallback em Docker.

### Ledger de commits da versão (100% rastreável)
- `3.0.2+git.<pending>` - enforce clone authorization by profile/entity recursive scope
- `3.0.2+git.<pending>` - add plugin config UI for clone authorizations and validation script

## [3.0.1] - 2026-05-20

### Patch release
- Corrige a ordenação de perfis na configuração do plugin.
- Mantém ações de clonagem original e em massa liberadas para perfil super-admin.
- Separa campos fixos do formulário (`Título`, `Tipo`, `Categoria`, `Conteúdo`) da lista configurável de cópia.
- Remove `Contrato` da lista configurável de campos clonados.
- Ajusta textos de UI da política de cópia e limpa traduções obsoletas do escopo de permissões removido.

### Ledger de commits da versão (100% rastreável)
- `3.0.1+git.<pending>` - corrige ordenação de perfis na tela de configuração
- `3.0.1+git.<pending>` - remove governança de permissão da configuração e runtime
- `3.0.1+git.<pending>` - alinha DRE e prompts com fronteira clone x permission
- `3.0.1+git.<pending>` - libera clone original e massivo para perfil super-admin
- `3.0.1+git.<pending>` - separa campos fixos do formulário da lista configurável de cópia
- `3.0.1+git.<pending>` - remove contrato da lista configurável de campos clonados
- `3.0.1+git.<pending>` - bump de versão para 3.0.1 e atualização de changelog/i18n

## [3.0.0] - 2026-05-19

### Major release
- Separa o domínio de permissões/bloqueios do runtime do `ebenezerclone`, mantendo foco em clonagem.
- Remove enforcement de permissões no hook `pre_item_update` deste plugin.
- Mantém endpoint de permissões em modo neutro para compatibilidade transitória.
- Reduz JS do plugin para integração compartilhada de visibilidade de tickets vinculados.
- Adiciona kit documental para criação do novo plugin de permissões (`DRE` e prompts operacionais).

### Ledger de commits da versão (100% rastreável)
- `3.0.0+git.<pending>` - separa runtime de permissao do fluxo de clonagem
- `3.0.0+git.<pending>` - adiciona kit de DRE e prompts para novo plugin de permissoes
- `3.0.0+git.<pending>` - mantem js de integracao de vinculados ativo
- `3.0.0+git.<pending>` - bump de versao para 3.0.0 e registro de major release

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

### Permissões por perfil (PATCH)
- Adiciona base de matriz de permissões por perfil nas configurações do plugin.
- Inclui catálogo de permissões com suporte a tooltip funcional por opção.
- Mantém fallback para comportamento legado quando não houver configuração de perfil.

### Ledger de commits da versão (100% rastreável)
- `1.0.3+git.<pending>` - Estrutura matriz de permissões por perfil no config do plugin

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
