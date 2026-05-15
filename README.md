# Ebenezer Clone

Plugin para GLPI 10.0.x que adiciona uma aba para clonar chamados com rastreabilidade entre ticket de origem e ticket novo.

- Versão: 2.0.0
- Autor: Renato Valadares
- Licença: GPL v2+

## Rastreabilidade e versionamento

- Changelog: `plugins/ebenezerclone/CHANGELOG.md`
- Matriz de rastreio (DRE/commits/PRs/tags): `plugins/ebenezerclone/TRACEABILITY.md`
- Regra de representação por commit: `<release_semver>+git.<short_sha>`

## O que o plugin faz

- Adiciona a aba **Clonar chamado** dentro do ticket.
- Cria um novo ticket com vínculo explícito para o ticket de origem.
- Registra acompanhamento automático em ambos os tickets (origem e clone).
- Registra histórico (`Log`) de clonagem nos dois lados.
- Controla quais campos podem ser editados no formulário de clonagem.
- Restringe edição de propriedades do ticket após abertura, permitindo foco na gestão de atribuídos conforme regras de perfil/grupo.

## Requisitos

- GLPI `>= 10.0.0` e `< 10.1.0`
- Plugin em `plugins/ebenezerclone`
- Permissões de ticket no perfil e direito do plugin habilitado

## Instalação

1. Copie a pasta `ebenezerclone` para `glpi/plugins/`.
2. No GLPI, acesse **Administração > Plugins**.
3. Instale e ative o plugin **Ebenezer Clone**.
4. Configure direitos em **Administração > Perfis > (Perfil) > Ebenezer Clone**.

## Permissões

Direito principal do plugin:

- `plugin_ebenezerclone_clone`

Com esse direito e permissões de ticket no perfil:

- a aba **Clonar chamado** fica visível para quem pode ler o ticket e criar tickets;
- a ação de clonagem exige permissão de criação de ticket.

## Como usar

1. Abra o chamado de origem.
2. Entre na aba **Clonar chamado**.
3. Ajuste os campos permitidos pela configuração (Título, Tipo, Categoria).
4. Clique em **Clonar chamado**.
5. O GLPI redireciona para o novo ticket criado.

## Regras de negócio (clonagem)

### 1) Campos do formulário por modo

Em **Configurar > Geral > Ebenezer Clone**, cada campo pode ser:

- `Editable`: usuário pode alterar
- `Read-only`: aparece bloqueado
- `Hidden`: não aparece

Campos configuráveis atualmente:

- Título (`name`)
- Tipo (`type`)
- Categoria (`itilcategories_id`)

Regra aplicada no backend:

- se campo está `Editable`, usa valor informado no clone;
- se está `Read-only` ou `Hidden`, usa o valor do ticket de origem.

### 2) Regra de título do clone

O título final é calculado pela categoria selecionada (quando existir), usando o caminho completo da categoria (`completename`) com separador `|`.

Exemplo:

- Categoria: `Infraestrutura > Servidores > Linux`
- Título gerado: `Infraestrutura | Servidores | Linux`

Observação:

- essa regra é aplicada no frontend (preview) e reaplicada no backend, garantindo consistência.

### 3) Regras de criação do novo ticket

Ao clonar, o plugin cria o novo ticket com:

- `status = Atribuído`
- `date = horário corrente da sessão`
- `entities_id` do ticket de origem (ou da categoria, quando categoria possui entidade específica)
- `requesttypes_id`, `urgency`, `impact`, `priority`, `locations_id` copiados da origem
- conteúdo (`content`) da origem quando não informado valor válido no clone

### 4) Atores copiados

São copiados do ticket de origem para o clone:

- usuários requerentes
- usuários observadores
- grupos requerentes
- grupos observadores

### 5) Itens e rastreabilidade

Após criar o clone, o plugin:

- copia os itens vinculados (`Item_Ticket`) do ticket de origem;
- cria vínculo entre clone e origem em `Ticket_Ticket`;
- adiciona acompanhamento automático nos dois tickets;
- grava histórico de clonagem nos dois tickets.

### 6) Validação de categoria obrigatória

Se o template ITIL exigir categoria para o tipo/cenário informado, a clonagem falha quando a categoria não for válida/definida.

## Regras de negócio (edição após abertura)

No `pre_item_update` de Ticket, o plugin aplica proteção:

- bloqueia alteração de propriedades do ticket após abertura (campos como tipo, categoria, status, prioridade, SLA, datas etc.);
- permite alteração de atribuídos apenas quando a regra de permissão for atendida.

Permissão para editar atribuídos:

- sempre permitido em ticket novo;
- negado em ticket fechado;
- permitido para perfil ativo `id = 10`;
- permitido quando usuário pertence a um grupo já atribuído ao ticket.

Quando não permitido, o plugin remove a mutação de atribuídos da requisição e registra mensagem de erro.

## Configurações disponíveis

Em **Configurar > Geral > Ebenezer Clone**:

- modo dos campos do formulário de clone;
- opção **Default remove author from assigned**.

Observação importante:

- a opção **Default remove author from assigned** está persistida em configuração, mas não é aplicada na rotina de clonagem atual.

## Estrutura do plugin

- `setup.php`: metadados, hooks e registro das classes
- `hook.php`: instalação/desinstalação e hook `pre_item_update`
- `front/clone.form.php`: endpoint de submissão da clonagem
- `front/config.form.php`: acesso à configuração
- `inc/clone.class.php`: lógica de clonagem, validações e regras de edição
- `inc/config.class.php`: modos de campos e configurações
- `inc/profile.class.php`: direitos no perfil
- `js/ebenezerclone.js`: ajuste de UI para ação de clone
- `locales/pt_BR.po`: traduções

## Limitações e pontos de atenção

- Compatível com GLPI `10.0.x` (`>= 10.0.0` e `< 10.1.0`).
- Fuso/horário do servidor impactam data/hora inicial do clone.
- Reabra a sessão após ajustes de perfil/direitos para refletir permissões.

## Validação rápida pós-ajuste

1. Acesse um ticket e confirme presença da aba **Clonar chamado**.
2. Teste os 3 modos de campo (`Editable`, `Read-only`, `Hidden`).
3. Clone com categorias diferentes e valide título gerado.
4. Confirme followup e histórico em ambos os tickets.
5. Tente editar propriedades bloqueadas após abertura e valide bloqueio.

## Autor

Renato Valadares

## Politica de precedencia para propriedades do chamado

Objetivo operacional:
- Usar a matriz por perfil (`Bloquear` / `Habilitar` / `Ignorar`) como controle principal.
- Manter hardcode somente para excecoes criticas do core.

Prioridade pratica:
1. Regras nativas criticas do GLPI (core) para campo especifico.
2. Politica por perfil do plugin para o campo (`Bloquear`, `Habilitar`, `Ignorar`).
3. Quando em `Ignorar`, o plugin nao interfere e o controle fica com core/outros plugins.

Significado por campo:
- `Bloquear`: plugin deixa o campo em leitura e remove mutacao na atualizacao.
- `Habilitar`: plugin permite edicao do campo.
- `Ignorar`: plugin nao aplica lock nem filtro no update para o campo.

Excecoes criticas de core mantidas pelo plugin:
- `itilcategories_id` quando regra nativa `canRequesterUpdateItem()` permite atualizacao.
- `priority` quando o perfil tem direito nativo `Ticket::CHANGEPRIORITY`.
- `status` e controlado somente pela politica do plugin (`Bloquear`/`Habilitar`/`Ignorar`), sem override hardcoded.

Logs tecnicos de conflito (debug):
- `properties_update_allowed_by_core`: core prevaleceu sobre bloqueio do plugin.
- `properties_update_blocked_by_plugin`: plugin bloqueou campos conforme matriz.

### Opcao global: liberar categoria vazia para edicao

Em **Permissoes globais**, a opcao **Allow empty category edition** controla este comportamento:
- Marcada: se `itilcategories_id` estiver vazio (`0`), o plugin nao bloqueia a categoria no chamado.
- Desmarcada: a categoria segue somente a politica por perfil (`Bloquear`/`Habilitar`/`Ignorar`) e regras do core.

Cenarios:
1. Marcada + categoria vazia + perfil em `Bloquear` => categoria editavel.
2. Desmarcada + categoria vazia + perfil em `Bloquear` => categoria bloqueada.
3. Categoria preenchida => segue regras normais (perfil/core).

### Regras de i18n (obrigatorio)
- Qualquer novo texto de interface criado no plugin deve ter traducao no `locales/pt_BR.po`.
- Nenhum rótulo/tooltip novo pode permanecer em inglês na interface.

### Default das propriedades do chamado
- O valor padrao por propriedade na matriz de perfil e `Bloquear`.
- Em migracoes de configuracoes antigas, propriedades sem valor explicito passam para `Bloquear` ao salvar configuracoes.

### Checklist anti-mojibake (obrigatorio)
Antes de recompilar locale, executar:
- `Select-String -Path plugins/ebenezerclone/locales/pt_BR.po -Pattern "Ã|Â|\?"`

Se houver ocorrencias:
- Corrigir os `msgstr` afetados (preferir ASCII simples quando houver risco de encoding local).
- Salvar o arquivo como UTF-8.
- Recompilar `pt_BR.mo` com `msgfmt`.

Regra mandatória:
- Não publicar alteração de i18n com ocorrências de `Ã`, `Â` ou `?` em `msgstr`.
