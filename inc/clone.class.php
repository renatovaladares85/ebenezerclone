<?php

if (!defined('GLPI_ROOT')) {
    die('Sorry. You cannot access directly to this file');
}

class PluginEbenezercloneClone extends CommonGLPI
{
    public static $rightname = 'plugin_ebenezerclone_clone';
    private const TIMELINE_LOG_SEARCH_OPTION = 21;
    private const LOCKED_PROPERTY_FIELDS_AFTER_OPEN = [
        'date',
        'time_to_resolve',
        'solvedate',
        'closedate',
        'type',
        'itilcategories_id',
        'status',
        'requesttypes_id',
        'urgency',
        'impact',
        'priority',
        'locations_id',
        '_contracts_id',
        'actiontime',
        'slas_id_ttr',
        'slas_id_tto',
        'olas_id_ttr',
        'olas_id_tto',
        'time_to_own',
    ];
    private const TIMELINE_LOG_CLONE_CREATED = 'timeline_log_clone_created';
    private const TIMELINE_LOG_CLONE_SOURCE = 'timeline_log_clone_source';
    private const TIMELINE_LOG_TICKET_LINK = 'timeline_log_ticket_link';
    private const TIMELINE_LOG_FOLLOWUPS = 'timeline_log_followups';
    private const TIMELINE_LOG_ITEMS_COPIED = 'timeline_log_items_copied';
    private const TIMELINE_LOG_ACTORS_COPIED = 'timeline_log_actors_copied';
    private const TIMELINE_LOG_CLONE_FAILURE = 'timeline_log_clone_failure';
    private const ACTOR_ROLE_REQUESTER = 'requester';
    private const ACTOR_ROLE_OBSERVER = 'observer';
    private const ACTOR_ROLE_ASSIGN = 'assign';

    public function getRights($interface = 'central')
    {
        return [
            CREATE => __('Create'),
            READ   => __('Read'),
            UPDATE => __('Update'),
            PURGE  => [
                'short' => __('Purge'),
                'long'  => _x('button', 'Delete permanently'),
            ],
        ];
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Ticket && self::canShowForItem($item)) {
            return self::createTabEntry(t_ebenezerclone('Clonar chamado'));
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Ticket && self::canShowForItem($item)) {
            self::showCloneForm($item);
        }
        return true;
    }

    private static function canShowForItem(Ticket $ticket)
    {
        return $ticket->can($ticket->getID(), READ)
            && self::canCloneTicketInCurrentProfile((int) $ticket->getField('entities_id'))
            && Ticket::canCreate();
    }

    public static function canCloneTicketInCurrentProfile(?int $entity_id = null): bool
    {
        if (!Ticket::canCreate() || !PluginEbenezercloneConfig::isCloneOperationsEnabled()) {
            return false;
        }

        $permission = PluginEbenezercloneConfig::hasProfilePermission(
            PluginEbenezercloneConfig::PERMISSION_CLONE_TICKET,
            null,
            (int) ($entity_id ?? ($_SESSION['glpiactive_entity'] ?? 0))
        );
        return $permission === true;
    }

    public static function canUseMassiveCloneActionInCurrentProfile(?int $entity_id = null): bool
    {
        if (!Ticket::canCreate() || !PluginEbenezercloneConfig::isCloneOperationsEnabled()) {
            return false;
        }

        // Global OFF: Ebenezer does not interfere for this rule.
        if (!PluginEbenezercloneConfig::isGlobalCloneActionsBlocked()) {
            return true;
        }

        // Global ON: profile matrix can explicitly allow this action.
        $permission = PluginEbenezercloneConfig::hasProfilePermission(
            PluginEbenezercloneConfig::PERMISSION_MASSIVE_CLONE,
            null,
            (int) ($entity_id ?? ($_SESSION['glpiactive_entity'] ?? 0))
        );
        return $permission === true;
    }

    public static function canUseTicketCloneActionInCurrentProfile(?int $entity_id = null): bool
    {
        if (!Ticket::canCreate() || !PluginEbenezercloneConfig::isCloneOperationsEnabled()) {
            return false;
        }

        // Global OFF: Ebenezer does not interfere for this rule.
        if (!PluginEbenezercloneConfig::isGlobalCloneActionsBlocked()) {
            return true;
        }

        // Global ON: profile matrix can explicitly allow this action.
        $permission = PluginEbenezercloneConfig::hasProfilePermission(
            PluginEbenezercloneConfig::PERMISSION_TICKET_CLONE_ACTION,
            null,
            (int) ($entity_id ?? ($_SESSION['glpiactive_entity'] ?? 0))
        );
        return $permission === true;
    }

    public static function showCloneForm(Ticket $ticket)
    {
        global $CFG_GLPI;

        $definitions = PluginEbenezercloneConfig::getFieldDefinitions();
        uasort($definitions, fn($a, $b) => $a['order'] <=> $b['order']);
        $field_modes = self::getEffectiveFieldModesForCurrentProfile((int) $ticket->getField('entities_id'));
        $entity_id = (int) $ticket->getField('entities_id');
        $rand = mt_rand();
        $form_name = 'ebenezerclone_form_' . $rand;

        $field_values = [];
        foreach ($definitions as $key => $def) {
            if ($def['ticket_field'] !== null) {
                $field_values[$key] = $ticket->getField($def['ticket_field']);
            } else {
                $field_values[$key] = null;
            }
        }

        $widgets = self::buildWidgets($definitions, $field_modes, $field_values, $entity_id, $form_name, $rand);

        $action = Plugin::getWebDir('ebenezerclone') . '/front/clone.form.php';

        echo "<form name='$form_name' id='$form_name' method='post' action='$action' onsubmit='return syncCloneFields$rand();'>";
        echo "<div class='spaced' id='tabsbody'>";

        echo "<div class='center mb-3'><strong>" . t_ebenezerclone('Clonar chamado') . "</strong></div>";

        foreach ($definitions as $key => $def) {
            $mode = $field_modes[$key] ?? PluginEbenezercloneConfig::MODE_EDITABLE;
            $value = $field_values[$key] ?? null;

            if ($mode === PluginEbenezercloneConfig::MODE_HIDDEN) {
                echo Html::hidden($def['form_name'], ['value' => $value]);
                continue;
            }

            echo "<div class='form-field row col-12 mb-2'>";
            echo "<label class='col-form-label col-xxl-4 text-xxl-end'>" . $def['label'] . "</label>";
            echo "<div class='col-xxl-8 field-container'>";
            self::renderField($key, $def, $mode, $value, $widgets, $rand);
            echo "</div></div>";
        }

        echo Html::hidden('itemtype', ['value' => $ticket->getType()]);
        echo Html::hidden('id', ['value' => $ticket->getID()]);
        foreach ($definitions as $key => $def) {
            echo Html::hidden($def['clone_name'], ['value' => $field_values[$key] ?? '']);
        }
        echo "<div class='form-field row col-12 mb-2'>";
        echo "<div class='col-xxl-4'></div>";
        echo "<div class='col-xxl-8 field-container'>";
        echo Html::submit(t_ebenezerclone('Clonar chamado'), [
            'name'  => '_clone',
            'class' => 'btn btn-primary',
        ]);
        echo "</div></div>";

        echo "</div>";
        Html::closeForm();

        $category_mode = $field_modes['category'] ?? PluginEbenezercloneConfig::MODE_EDITABLE;
        $is_category_readonly = ($category_mode === PluginEbenezercloneConfig::MODE_READONLY);
        self::renderSyncScript(
            $definitions,
            $form_name,
            $entity_id,
            $rand,
            $is_category_readonly,
            (string) ($field_values['name'] ?? ''),
            (int) ($field_values['category'] ?? 0)
        );
    }

    private static function buildWidgets(array $definitions, array $field_modes, array $field_values, int $entity_id, string $form_name, int $rand)
    {
        $widgets = [];
        $reload_js = "onCloneTypeChange$rand();";

        foreach ($definitions as $key => $def) {
            $mode = $field_modes[$key] ?? PluginEbenezercloneConfig::MODE_EDITABLE;
            if ($mode === PluginEbenezercloneConfig::MODE_HIDDEN) {
                continue;
            }

            switch ($def['input_type']) {
                case 'dropdown_type':
                    $widgets[$key] = Ticket::dropdownType($def['form_name'], [
                        'value'     => (int) ($field_values[$key] ?? 0),
                        'on_change' => $reload_js,
                        'display'   => false,
                        'disabled'  => ($mode === PluginEbenezercloneConfig::MODE_READONLY),
                    ]);
                    break;

                case 'dropdown_category':
                    $type = (int) ($field_values['type'] ?? 0);
                    $cat_options = [
                        'name'    => $def['form_name'],
                        'value'   => (int) ($field_values[$key] ?? 0),
                        'entity'  => $entity_id,
                        'width'   => '100%',
                        'display' => false,
                    ];
                    if ($type === Ticket::INCIDENT_TYPE) {
                        $cat_options['condition'] = ['is_incident' => 1];
                    } elseif ($type === Ticket::DEMAND_TYPE) {
                        $cat_options['condition'] = ['is_request' => 1];
                    }
                    if ($mode === PluginEbenezercloneConfig::MODE_READONLY) {
                        $cat_options['disabled'] = true;
                    }
                    $widgets[$key] = ITILCategory::dropdown($cat_options);
                    break;
            }
        }

        return $widgets;
    }

    private static function renderField(string $key, array $def, string $mode, $value, array $widgets, int $rand)
    {
        $is_readonly = ($mode === PluginEbenezercloneConfig::MODE_READONLY);

        switch ($def['input_type']) {
            case 'text':
                echo Html::input($def['form_name'], [
                    'value'    => (string) $value,
                    'size'     => 80,
                    'class'    => 'form-control',
                    'readonly' => $is_readonly,
                ]);
                break;

            case 'dropdown_type':
                echo $widgets[$key] ?? '';
                break;

            case 'dropdown_category':
                $dropdown_html = $widgets[$key] ?? '';
                echo "<span id='category_block_$rand'><div class='field-container'>" . $dropdown_html . "</div></span>";
                break;

            case 'textarea':
                Html::textarea([
                    'name'            => $def['form_name'],
                    'value'           => (string) $value,
                    'enable_richtext' => true,
                    'enable_images'   => false,
                    'rows'            => 8,
                    'cols'            => 80,
                    'class'           => 'form-control',
                    'readonly'        => $is_readonly,
                ]);
                break;

            case 'checkbox':
                Html::showCheckbox([
                    'name'     => $def['form_name'],
                    'checked'  => (bool) $value,
                    'disabled' => $is_readonly,
                ]);
                break;
        }
    }

    private static function renderSyncScript(
        array $definitions,
        string $form_name,
        int $entity_id,
        int $rand,
        bool $is_category_readonly,
        string $source_name,
        int $source_category_id
    )
    {
        global $CFG_GLPI;
        $ajax_url = $CFG_GLPI['root_doc'] . '/ajax/dropdownTicketCategories.php';
        $is_category_readonly_js = $is_category_readonly ? 'true' : 'false';
        $wait_category_message_js = json_encode(
            t_ebenezerclone('Please wait for the category field to finish loading.'),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $category_mandatory_message_js = json_encode(
            t_ebenezerclone('Category is mandatory.'),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $type_mandatory_message_js = json_encode(
            t_ebenezerclone('Type is mandatory.'),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $source_name_js = json_encode($source_name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $source_category_id_js = (int) $source_category_id;

        $sync_lines = [];
        foreach ($definitions as $key => $def) {
            $fn = $def['form_name'];
            $cn = $def['clone_name'];
            if ($key === 'name') {
                $sync_lines[] = "$('#{$form_name} [name={$cn}]').val($('#{$form_name} [name={$fn}]').val());";
            } elseif ($key === 'category') {
                $sync_lines[] = "var v_{$key} = $('#{$form_name} [name={$fn}]').val();";
                $sync_lines[] = "if (v_{$key} === undefined || v_{$key} === null) { v_{$key} = ''; }";
                $sync_lines[] = "$('#{$form_name} [name={$cn}]').val(v_{$key});";
            } elseif ($def['input_type'] === 'checkbox') {
                $sync_lines[] = "var f_{$key} = $('#{$form_name} [name={$fn}]');";
                $sync_lines[] = "var v_{$key} = 0;";
                $sync_lines[] = "if (f_{$key}.length) {";
                $sync_lines[] = "  if (f_{$key}.attr('type') === 'checkbox') { v_{$key} = f_{$key}.is(':checked') ? 1 : 0; }";
                $sync_lines[] = "  else { v_{$key} = parseInt(f_{$key}.val(), 10) || 0; }";
                $sync_lines[] = "}";
                $sync_lines[] = "$('#{$form_name} [name={$cn}]').val(v_{$key});";
            } else {
                $sync_lines[] = "$('#{$form_name} [name={$cn}]').val($('#{$form_name} [name={$fn}]').val());";
            }
        }
        $sync_body = implode("\n    ", $sync_lines);

        $js = <<<JAVASCRIPT
var getSelectedCloneCategoryLabel$rand = function() {
    var field = $('#$form_name [name=itilcategories_id]');
    if (!field.length) {
        return '';
    }

    var rendered = '';
    var select2Container = field.next('.select2');
    if (select2Container.length) {
        rendered = (select2Container.find('.select2-selection__rendered').first().text() || '').trim();
    }

    var label = rendered;
    if (!label || label.indexOf('>') === -1) {
        label = (field.find('option:selected').text() || '').trim();
    }

    return label;
};

var cloneCurrentType$rand = null;
var cloneCategoryLoading$rand = false;
var cloneCategoryReadonly$rand = $is_category_readonly_js;
var waitCategoryMessage$rand = $wait_category_message_js;
var mandatoryCategoryMessage$rand = $category_mandatory_message_js;
var mandatoryTypeMessage$rand = $type_mandatory_message_js;
var cloneSourceName$rand = $source_name_js;
var cloneSourceCategoryId$rand = $source_category_id_js;

var setCloneCategoryLoading$rand = function(loading) {
    cloneCategoryLoading$rand = loading;

    var field = $('#$form_name [name=itilcategories_id]');
    if (!field.length) {
        return;
    }

    if (loading) {
        field.prop('disabled', true);
        return;
    }

    if (!cloneCategoryReadonly$rand) {
        field.prop('disabled', false);
    }
};

var formatCloneTitleFromCategoryLabel$rand = function(categoryLabel, ticketIdentifier) {
    if (!categoryLabel) {
        return '';
    }

    var parts = categoryLabel.split('>')
        .map(function(part) { return part.trim(); })
        .filter(function(part) { return part.length > 0; });

    if (!parts.length) {
        return '';
    }

    if (!parts.length) {
        return '';
    }
    var core = parts.join(' | ');

    return ticketIdentifier ? (core + ' (' + ticketIdentifier + ')') : core;
};

var buildCloneNameFromCategory$rand = function() {
    var label = getSelectedCloneCategoryLabel$rand();
    return formatCloneTitleFromCategoryLabel$rand(label, '');
};

var refreshCloneNamePreview$rand = function() {
    var selectedCategory = parseInt($('#$form_name [name=itilcategories_id]').val(), 10) || 0;
    var shouldAutoGenerate = selectedCategory > 0 && selectedCategory !== cloneSourceCategoryId$rand;

    var nextTitle = cloneSourceName$rand;
    if (shouldAutoGenerate) {
        var built = buildCloneNameFromCategory$rand();
        if (built) {
            nextTitle = built;
        }
    }

    var nameField = $('#$form_name [name=name]');
    if (nameField.length) {
        nameField.val(nextTitle);
    }

    var cloneNameField = $('#$form_name [name=clone_name]');
    if (cloneNameField.length) {
        cloneNameField.val(nextTitle);
    }
};

var reloadCloneCategory$rand = function(resetValue) {
    if (cloneCategoryLoading$rand) {
        return;
    }

    var type = $('#$form_name [name=type]').val();
    var currentValue = 0;
    if (!resetValue) {
        currentValue = parseInt($('#$form_name [name=itilcategories_id]').val(), 10) || 0;
        if (currentValue <= 0) {
            currentValue = parseInt($('#$form_name [name=clone_itilcategories_id]').val(), 10) || 0;
        }
    }

    setCloneCategoryLoading$rand(true);

    $('#category_block_$rand .field-container').load(
        '$ajax_url',
        {
            'type': type,
            'entity_restrict': $entity_id,
            'value': currentValue
        },
        function() {
            setCloneCategoryLoading$rand(false);
        }
    );
};

var onCloneTypeChange$rand = function() {
    var selectedType = ($('#$form_name [name=type]').val() || '').toString();
    var resetValue = false;

    if (cloneCurrentType$rand !== null && cloneCurrentType$rand !== selectedType) {
        resetValue = true;
    }

    cloneCurrentType$rand = selectedType;
    reloadCloneCategory$rand(resetValue);
};

var syncCloneFields$rand = function() {
    if (cloneCategoryLoading$rand) {
        alert(waitCategoryMessage$rand);
        return false;
    }

    var selectedType = parseInt($('#$form_name [name=type]').val(), 10) || 0;
    var selectedCategory = parseInt($('#$form_name [name=itilcategories_id]').val(), 10) || 0;
    if (selectedType <= 0) {
        alert(mandatoryTypeMessage$rand);
        return false;
    }

    if (selectedCategory <= 0) {
        alert(mandatoryCategoryMessage$rand);
        return false;
    }

    refreshCloneNamePreview$rand();
    $sync_body
    return true;
};

$(document).on('change', '#$form_name [name=itilcategories_id]', function() {
    refreshCloneNamePreview$rand();
});

cloneCurrentType$rand = ($('#$form_name [name=type]').val() || '').toString();
reloadCloneCategory$rand(false);
JAVASCRIPT;

        echo Html::scriptBlock($js);
    }

    public static function cloneTicket(array $input)
    {
        if (!isset($input['id']) || !isset($input['name']) || !isset($input['type'])) {
            Session::addMessageAfterRedirect(__('Invalid request.'), false, ERROR);
            return null;
        }

        $ticket = new Ticket();
        if (!$ticket->getFromDB((int) $input['id'])) {
            Session::addMessageAfterRedirect(__('Item not found.'), false, ERROR);
            return null;
        }
        $ticket->check($ticket->getID(), READ);

        if (!Ticket::canCreate()) {
            Session::addMessageAfterRedirect(__('You do not have permission to create tickets.'), false, ERROR);
            return null;
        }

        if (!self::canCloneTicketInCurrentProfile((int) $ticket->getField('entities_id'))) {
            Session::addMessageAfterRedirect(__('You do not have permission to perform this action.'), false, ERROR);
            return null;
        }

        $field_modes = self::getEffectiveFieldModesForCurrentProfile((int) $ticket->getField('entities_id'));
        $definitions = PluginEbenezercloneConfig::getFieldDefinitions();
        $resolved = [];
        foreach ($definitions as $key => $def) {
            if ($def['ticket_field'] === null) {
                continue;
            }
            if ($field_modes[$key] === PluginEbenezercloneConfig::MODE_EDITABLE) {
                $clone_value = $input[$def['clone_name']] ?? null;
                $form_value  = $input[$def['form_name']] ?? null;

                if (self::isFilledInputValue($clone_value)) {
                    $resolved[$key] = $clone_value;
                } elseif (self::isFilledInputValue($form_value)) {
                    $resolved[$key] = $form_value;
                } elseif ($key === 'category') {
                    $resolved[$key] = 0;
                } else {
                    $resolved[$key] = $ticket->getField($def['ticket_field']);
                }
            } else {
                $resolved[$key] = $ticket->getField($def['ticket_field']);
            }
        }

        $name = (string) $ticket->getField('name');
        $content = (string) ($resolved['content'] ?? $ticket->getField('content'));
        $type = (int) ($resolved['type'] ?? $ticket->getField('type'));
        $source_category_id = (int) $ticket->getField('itilcategories_id');
        $itilcategories_id = (int) ($resolved['category'] ?? $ticket->getField('itilcategories_id'));
        $entities_id = (int) $ticket->getField('entities_id');

        if ($type <= 0) {
            Session::addMessageAfterRedirect(t_ebenezerclone('Type is mandatory.'), false, ERROR);
            return null;
        }

        if ($itilcategories_id <= 0) {
            Session::addMessageAfterRedirect(t_ebenezerclone('Category is mandatory.'), false, ERROR);
            return null;
        }

        if ($itilcategories_id > 0 && $itilcategories_id !== $source_category_id) {
            $computed_name_without_id = self::buildTitleFromCategory($itilcategories_id, '', '');
            if ($computed_name_without_id !== '') {
                $name = $computed_name_without_id;
            }
        }

        if ($itilcategories_id > 0) {
            $target_category = new ITILCategory();
            if ($target_category->getFromDB($itilcategories_id)) {
                $target_entity = (int) $target_category->getField('entities_id');
                if ($target_entity > 0) {
                    $entities_id = $target_entity;
                }
            }
        }

        $template = $ticket->getITILTemplateToUse(0, $type, $itilcategories_id, $entities_id);
        if ($template && $template->isMandatoryField('itilcategories_id') && $itilcategories_id <= 0) {
            Session::addMessageAfterRedirect(t_ebenezerclone('Category is mandatory.'), false, ERROR);
            return null;
        }

        $new = new Ticket();
        if ($content === '') {
            $content = (string) $ticket->getField('content');
        }

        $new_input = [];
        foreach (PluginEbenezercloneConfig::getCloneCopyTicketFieldKeys() as $field_key) {
            if (!PluginEbenezercloneConfig::shouldCopyCloneElement($field_key)) {
                continue;
            }

            switch ($field_key) {
                case 'entities_id':
                    $new_input[$field_key] = $entities_id;
                    break;
                case 'name':
                    $new_input[$field_key] = $name;
                    break;
                case 'type':
                    $new_input[$field_key] = $type;
                    break;
                case 'itilcategories_id':
                    $new_input[$field_key] = $itilcategories_id;
                    break;
                case 'content':
                    $new_input[$field_key] = $content;
                    break;
                default:
                    $new_input[$field_key] = $ticket->getField($field_key);
                    break;
            }
        }

        // Required creation fields keep safe fallback when policy is set to Ignore.
        if (!array_key_exists('entities_id', $new_input)) {
            $new_input['entities_id'] = $entities_id;
        }
        if (!array_key_exists('name', $new_input)) {
            $new_input['name'] = $name;
        }
        if (!array_key_exists('type', $new_input)) {
            $new_input['type'] = $type;
        }
        if (!array_key_exists('content', $new_input)) {
            $new_input['content'] = $content;
        }
        if (PluginEbenezercloneConfig::shouldForceAssignedStatusOnClone()) {
            $new_input['status'] = CommonITILObject::ASSIGNED;
        }
        if (!array_key_exists('date', $new_input)) {
            $new_input['date'] = $_SESSION['glpi_currenttime'];
        }

        $copied_actors = 0;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('actor_requester')) {
            $copied_actors += self::appendActorsForCloneRole($ticket, $new_input, CommonITILActor::REQUESTER);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('actor_observer')) {
            $copied_actors += self::appendActorsForCloneRole($ticket, $new_input, CommonITILActor::OBSERVER);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('actor_assign')) {
            $copied_actors += self::appendActorsForCloneRole($ticket, $new_input, CommonITILActor::ASSIGN);
        }

        $new_id = $new->add($new_input);
        if (!$new_id) {
            Toolbox::logDebug(
                'EBENEZERCLONE cloneTicket failed',
                [
                    'source_ticket_id' => (int) $ticket->getID(),
                    'source_entity_id' => (int) $ticket->getField('entities_id'),
                    'target_entity_id' => (int) $entities_id,
                    'new_input'        => $new_input,
                    'ticket_input'     => $input,
                    'ticket_errors'    => $new->getErrors(),
                ]
            );
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_CLONE_FAILURE,
                (int) $ticket->getID(),
                sprintf(
                    t_ebenezerclone('Clone failed for this ticket: %1$s'),
                    self::formatErrorsForLog($new->getErrors())
                )
            );
            Session::addMessageAfterRedirect(__('Failed to clone the ticket.'), false, ERROR);
            return null;
        }

        $copied_items = 0;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('items')) {
            $copied_items = self::copyItems($ticket, $new_id);
        }

        $copied_documents = 0;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('documents')) {
            $copied_documents = self::copyDocuments($ticket, $new_id);
        }

        if (PluginEbenezercloneConfig::shouldCopyCloneElement('followup_history')) {
            self::copyFollowupHistory($ticket, $new_id);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('tasks')) {
            self::copyTasks($ticket, $new_id);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('solutions')) {
            self::copySolutions($ticket, $new_id);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('validations')) {
            self::copyValidations($ticket, $new_id);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('satisfaction')) {
            self::copySatisfaction($ticket, $new_id);
        }

        $link_created = false;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('ticket_link')) {
            $link = new Ticket_Ticket();
            $link_created = (bool) $link->add([
                'tickets_id_1' => $new_id,
                'tickets_id_2' => $ticket->getID(),
                'link'         => Ticket_Ticket::LINK_TO,
            ]);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('ticket_relations')) {
            self::copyTicketRelations($ticket, $new_id);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('contracts')) {
            self::copyContracts($ticket, $new_id);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('projects')) {
            self::copyProjects($ticket, $new_id);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('problem_links')) {
            self::copyProblemLinks($ticket, $new_id);
        }
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('change_links')) {
            self::copyChangeLinks($ticket, $new_id);
        }

        $followups_added = 0;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('followups')) {
            $followups_added = self::addCloneFollowups($new_id, $ticket->getID());
        }
        self::addHistory(
            $new_id,
            $ticket->getID(),
            $copied_actors,
            $copied_items,
            $copied_documents,
            $followups_added,
            $link_created
        );

        $expected_category_for_validation = PluginEbenezercloneConfig::shouldCopyCloneElement('itilcategories_id')
            ? $itilcategories_id
            : 0;
        self::validatePostCloneConsistency($ticket, $new_id, $expected_category_for_validation);

        $new_ticket_link = Html::link(
            sprintf('#%1$s', $new_id),
            Ticket::getFormURLWithID($new_id)
        );
        Session::addMessageAfterRedirect(
            sprintf(t_ebenezerclone('Ticket successfully cloned: %1$s'), $new_ticket_link),
            false,
            INFO
        );

        return $new_id;
    }

    private static function addCloneFollowups($new_id, $old_id)
    {
        $old_ticket_url = Ticket::getFormURLWithID($old_id);
        $new_ticket_url = Ticket::getFormURLWithID($new_id);
        $old_ticket_link = Html::link(sprintf('#%1$s', $old_id), $old_ticket_url);
        $new_ticket_link = Html::link(sprintf('#%1$s', $new_id), $new_ticket_url);
        $followup = new ITILFollowup();
        $count = 0;

        if ($followup->add([
            'itemtype'  => Ticket::class,
            'items_id'  => $new_id,
            'content'   => sprintf(
                t_ebenezerclone('This ticket was cloned from ticket %1$s.'),
                $old_ticket_link
            ),
            'is_private' => 0,
        ])) {
            $count++;
        }

        if ($followup->add([
            'itemtype'  => Ticket::class,
            'items_id'  => $old_id,
            'content'   => sprintf(
                t_ebenezerclone('Ticket %1$s was created as a clone of this ticket.'),
                $new_ticket_link
            ),
            'is_private' => 0,
        ])) {
            $count++;
        }

        return $count;
    }

    private static function appendActorsForCloneRole(Ticket $ticket, array &$input, int $role): int
    {
        $count = 0;
        $suffix = self::getActorInputSuffixByRole($role);
        if ($suffix === '') {
            return 0;
        }

        $users = $ticket->getUsers($role);
        if (is_array($users) && count($users)) {
            $input['_users_id_' . $suffix] = [];
            foreach ($users as $user) {
                $user_id = (int) ($user['users_id'] ?? 0);
                if ($user_id <= 0) {
                    continue;
                }
                $input['_users_id_' . $suffix][] = $user_id;
                $count++;
            }
        }

        $groups = $ticket->getGroups($role);
        if (is_array($groups) && count($groups)) {
            $input['_groups_id_' . $suffix] = [];
            foreach ($groups as $group) {
                $group_id = (int) ($group['groups_id'] ?? 0);
                if ($group_id <= 0) {
                    continue;
                }
                $input['_groups_id_' . $suffix][] = $group_id;
                $count++;
            }
        }

        $suppliers = $ticket->getSuppliers($role);
        if (is_array($suppliers) && count($suppliers)) {
            $input['_suppliers_id_' . $suffix] = [];
            foreach ($suppliers as $supplier) {
                $supplier_id = (int) ($supplier['suppliers_id'] ?? 0);
                if ($supplier_id <= 0) {
                    continue;
                }
                $input['_suppliers_id_' . $suffix][] = $supplier_id;
                $count++;
            }
        }

        return $count;
    }

    private static function getActorInputSuffixByRole(int $role): string
    {
        if ($role === CommonITILActor::REQUESTER) {
            return 'requester';
        }
        if ($role === CommonITILActor::OBSERVER) {
            return 'observer';
        }
        if ($role === CommonITILActor::ASSIGN) {
            return 'assign';
        }

        return '';
    }

    private static function copyItems(Ticket $ticket, $new_id)
    {
        global $DB;

        $dbu = new DbUtils();
        $fkey = $dbu->getForeignKeyFieldForTable($ticket->getTable());
        $crit = [$fkey => $ticket->getID()];

        $item = new Item_Ticket();
        $count = 0;
        foreach ($DB->request($item->getTable(), $crit) as $dataitem) {
            if ($item->add([
                'itemtype' => $dataitem['itemtype'],
                'items_id' => $dataitem['items_id'],
                'tickets_id' => $new_id,
            ])) {
                $count++;
            }
        }

        return $count;
    }

    private static function copyDocuments(Ticket $ticket, int $new_id): int
    {
        return self::copyDocumentLinks($ticket->getType(), (int) $ticket->getID(), $ticket->getType(), $new_id);
    }

    private static function copyFollowupHistory(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $followup = new ITILFollowup();
        $count = 0;
        foreach ($DB->request([
            'FROM'  => $followup->getTable(),
            'WHERE' => [
                'itemtype' => $ticket->getType(),
                'items_id' => (int) $ticket->getID(),
            ],
            'ORDER' => ['date' => 'ASC', 'id' => 'ASC'],
        ]) as $row) {
            $content = (string) ($row['content'] ?? '');
            if ($content === '') {
                continue;
            }

            $new_followup_id = (int) $followup->add([
                'itemtype'          => $ticket->getType(),
                'items_id'          => $new_id,
                'content'           => $content,
                'is_private'        => (int) ($row['is_private'] ?? 0),
                'users_id'          => (int) ($row['users_id'] ?? 0),
                'requesttypes_id'   => (int) ($row['requesttypes_id'] ?? 0),
                'date'              => $row['date'] ?? null,
                'timeline_position' => (int) ($row['timeline_position'] ?? 0),
                '_disablenotif'     => true,
            ]);
            if ($new_followup_id <= 0) {
                Toolbox::logDebug('EBENEZERCLONE copyFollowupHistory failed', [
                    'source_ticket_id' => (int) $ticket->getID(),
                    'target_ticket_id' => $new_id,
                    'source_followup'  => (int) ($row['id'] ?? 0),
                ]);
                continue;
            }

            $count++;
            if (PluginEbenezercloneConfig::shouldCopyCloneElement('followup_documents')) {
                self::copyDocumentLinks(ITILFollowup::class, (int) $row['id'], ITILFollowup::class, $new_followup_id);
            }
        }

        return $count;
    }

    private static function copyTasks(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $task = new TicketTask();
        $count = 0;
        foreach ($DB->request([
            'FROM'  => $task->getTable(),
            'WHERE' => ['tickets_id' => (int) $ticket->getID()],
            'ORDER' => ['date' => 'ASC', 'id' => 'ASC'],
        ]) as $row) {
            $clone_row = $row;
            unset($clone_row['id']);
            $clone_row['tickets_id'] = $new_id;
            $clone_row['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $clone_row['sourceitems_id'] = 0;
            $clone_row['sourceof_items_id'] = 0;

            if (!$DB->insert($task->getTable(), $clone_row)) {
                Toolbox::logDebug('EBENEZERCLONE copyTasks failed', [
                    'source_ticket_id' => (int) $ticket->getID(),
                    'target_ticket_id' => $new_id,
                    'source_task_id'   => (int) ($row['id'] ?? 0),
                ]);
                continue;
            }

            $new_task_id = (int) $DB->insertId();
            $count++;
            if (PluginEbenezercloneConfig::shouldCopyCloneElement('task_documents')) {
                self::copyDocumentLinks(TicketTask::class, (int) $row['id'], TicketTask::class, $new_task_id);
            }
        }

        return $count;
    }

    private static function copySolutions(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $solution = new ITILSolution();
        $count = 0;
        foreach ($DB->request([
            'FROM'  => $solution->getTable(),
            'WHERE' => [
                'itemtype' => $ticket->getType(),
                'items_id' => (int) $ticket->getID(),
            ],
            'ORDER' => ['date_creation' => 'ASC', 'id' => 'ASC'],
        ]) as $row) {
            $clone_row = $row;
            unset($clone_row['id']);
            $clone_row['itemtype'] = $ticket->getType();
            $clone_row['items_id'] = $new_id;
            $clone_row['itilfollowups_id'] = null;

            if (!$DB->insert($solution->getTable(), $clone_row)) {
                Toolbox::logDebug('EBENEZERCLONE copySolutions failed', [
                    'source_ticket_id' => (int) $ticket->getID(),
                    'target_ticket_id' => $new_id,
                    'source_solution'  => (int) ($row['id'] ?? 0),
                ]);
                continue;
            }

            $new_solution_id = (int) $DB->insertId();
            $count++;
            if (PluginEbenezercloneConfig::shouldCopyCloneElement('solution_documents')) {
                self::copyDocumentLinks(ITILSolution::class, (int) $row['id'], ITILSolution::class, $new_solution_id);
            }
        }

        return $count;
    }

    private static function copyValidations(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $validation = new TicketValidation();
        $count = 0;
        foreach ($DB->request([
            'FROM'  => $validation->getTable(),
            'WHERE' => ['tickets_id' => (int) $ticket->getID()],
            'ORDER' => ['submission_date' => 'ASC', 'id' => 'ASC'],
        ]) as $row) {
            $clone_row = $row;
            unset($clone_row['id']);
            $clone_row['tickets_id'] = $new_id;

            if (!$DB->insert($validation->getTable(), $clone_row)) {
                Toolbox::logDebug('EBENEZERCLONE copyValidations failed', [
                    'source_ticket_id'     => (int) $ticket->getID(),
                    'target_ticket_id'     => $new_id,
                    'source_validation_id' => (int) ($row['id'] ?? 0),
                ]);
                continue;
            }

            $new_validation_id = (int) $DB->insertId();
            $count++;
            if (PluginEbenezercloneConfig::shouldCopyCloneElement('validation_documents')) {
                self::copyDocumentLinks(TicketValidation::class, (int) $row['id'], TicketValidation::class, $new_validation_id);
            }
        }

        return $count;
    }

    private static function copySatisfaction(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $satisfaction = new TicketSatisfaction();
        foreach ($DB->request($satisfaction->getTable(), ['tickets_id' => (int) $ticket->getID()]) as $row) {
            $clone_row = $row;
            unset($clone_row['id']);
            $clone_row['tickets_id'] = $new_id;
            if ($DB->insert($satisfaction->getTable(), $clone_row)) {
                return 1;
            }

            Toolbox::logDebug('EBENEZERCLONE copySatisfaction failed', [
                'source_ticket_id' => (int) $ticket->getID(),
                'target_ticket_id' => $new_id,
            ]);
        }

        return 0;
    }

    private static function copyContracts(Ticket $ticket, int $new_id): int
    {
        return self::copySimpleRelationRows(
            Ticket_Contract::getTable(),
            ['tickets_id' => (int) $ticket->getID()],
            ['tickets_id' => $new_id]
        );
    }

    private static function copyProjects(Ticket $ticket, int $new_id): int
    {
        return self::copySimpleRelationRows(
            Itil_Project::getTable(),
            [
                'itemtype' => $ticket->getType(),
                'items_id' => (int) $ticket->getID(),
            ],
            [
                'itemtype' => $ticket->getType(),
                'items_id' => $new_id,
            ]
        );
    }

    private static function copyProblemLinks(Ticket $ticket, int $new_id): int
    {
        return self::copySimpleRelationRows(
            Problem_Ticket::getTable(),
            ['tickets_id' => (int) $ticket->getID()],
            ['tickets_id' => $new_id]
        );
    }

    private static function copyChangeLinks(Ticket $ticket, int $new_id): int
    {
        return self::copySimpleRelationRows(
            Change_Ticket::getTable(),
            ['tickets_id' => (int) $ticket->getID()],
            ['tickets_id' => $new_id]
        );
    }

    private static function copyTicketRelations(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $table = Ticket_Ticket::getTable();
        $count = 0;

        foreach ($DB->request($table, ['tickets_id_1' => (int) $ticket->getID()]) as $row) {
            $link = new Ticket_Ticket();
            if ($link->add([
                'tickets_id_1' => $new_id,
                'tickets_id_2' => (int) ($row['tickets_id_2'] ?? 0),
                'link'         => (int) ($row['link'] ?? Ticket_Ticket::LINK_TO),
            ])) {
                $count++;
            }
        }

        foreach ($DB->request($table, ['tickets_id_2' => (int) $ticket->getID()]) as $row) {
            $link = new Ticket_Ticket();
            if ($link->add([
                'tickets_id_1' => (int) ($row['tickets_id_1'] ?? 0),
                'tickets_id_2' => $new_id,
                'link'         => (int) ($row['link'] ?? Ticket_Ticket::LINK_TO),
            ])) {
                $count++;
            }
        }

        return $count;
    }

    private static function copySimpleRelationRows(string $table, array $where, array $overrides): int
    {
        global $DB;

        $count = 0;
        foreach ($DB->request($table, $where) as $row) {
            $clone_row = array_merge($row, $overrides);
            unset($clone_row['id']);
            if ($DB->insert($table, $clone_row)) {
                $count++;
            }
        }

        return $count;
    }

    private static function copyDocumentLinks(string $source_itemtype, int $source_id, string $target_itemtype, int $target_id): int
    {
        global $DB;

        $document_item = new Document_Item();
        $count = 0;
        foreach ($DB->request($document_item->getTable(), [
            'itemtype' => $source_itemtype,
            'items_id' => $source_id,
        ]) as $row) {
            $minimal_input = [
                'documents_id' => (int) $row['documents_id'],
                'itemtype'     => $target_itemtype,
                'items_id'     => $target_id,
                'entities_id'  => (int) ($row['entities_id'] ?? 0),
                'is_recursive' => (int) ($row['is_recursive'] ?? 1),
            ];

            $input = $minimal_input;
            $users_id = (int) ($row['users_id'] ?? 0);
            if ($users_id > 0) {
                $input['users_id'] = $users_id;
            }

            $timeline_position = (int) ($row['timeline_position'] ?? 0);
            if ($timeline_position !== 0) {
                $input['timeline_position'] = $timeline_position;
            }

            if ($document_item->add($input) || $document_item->add($minimal_input)) {
                $count++;
                continue;
            }

            Toolbox::logDebug('EBENEZERCLONE copyDocumentLinks failed', [
                'source_itemtype' => $source_itemtype,
                'source_id'       => $source_id,
                'target_itemtype' => $target_itemtype,
                'target_id'       => $target_id,
                'documents_id'    => (int) $row['documents_id'],
            ]);
        }

        return $count;
    }

    private static function addHistory(
        $new_id,
        $old_id,
        int $copied_actors,
        int $copied_items,
        int $copied_documents,
        int $followups_added,
        bool $link_created
    )
    {
        $has_create_log = countElementsInTable(Log::getTable(), [
            'itemtype'      => Ticket::class,
            'items_id'      => (int) $new_id,
            'linked_action' => Log::HISTORY_CREATE_ITEM,
        ]) > 0;

        // Keep core behavior as source of truth, but enforce the create event when missing.
        if (!$has_create_log) {
            Log::history(
                $new_id,
                Ticket::class,
                [0, '', ''],
                0,
                Log::HISTORY_CREATE_ITEM
            );
        }

        self::logTimelineMessageIfEnabled(
            self::TIMELINE_LOG_CLONE_CREATED,
            (int) $new_id,
            sprintf(t_ebenezerclone('Cloned from ticket #%1$s'), $old_id)
        );

        self::logTimelineMessageIfEnabled(
            self::TIMELINE_LOG_CLONE_SOURCE,
            (int) $old_id,
            sprintf(t_ebenezerclone('Cloned to ticket #%1$s'), $new_id)
        );

        if ($link_created) {
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_TICKET_LINK,
                (int) $new_id,
                sprintf(t_ebenezerclone('Link created with source ticket #%1$s'), $old_id)
            );
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_TICKET_LINK,
                (int) $old_id,
                sprintf(t_ebenezerclone('Link created with cloned ticket #%1$s'), $new_id)
            );
        }

        if ($copied_actors > 0) {
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_ACTORS_COPIED,
                (int) $new_id,
                sprintf(t_ebenezerclone('Copied %1$s actor(s) from source ticket'), $copied_actors)
            );
        }

        if ($copied_items > 0) {
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_ITEMS_COPIED,
                (int) $new_id,
                sprintf(t_ebenezerclone('Copied %1$s linked item(s) from source ticket'), $copied_items)
            );
        }

        if ($copied_documents > 0) {
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_ITEMS_COPIED,
                (int) $new_id,
                sprintf(t_ebenezerclone('Copied %1$s document link(s) from source ticket'), $copied_documents)
            );
        }

        if ($followups_added > 0) {
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_FOLLOWUPS,
                (int) $new_id,
                sprintf(t_ebenezerclone('Created %1$s informational followup(s) during clone'), $followups_added)
            );
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_FOLLOWUPS,
                (int) $old_id,
                sprintf(t_ebenezerclone('Created %1$s informational followup(s) during clone'), $followups_added)
            );
        }
    }

    private static function logTimelineMessageIfEnabled(string $config_key, int $ticket_id, string $message): void
    {
        if (!PluginEbenezercloneConfig::isTimelineLogEnabled($config_key)) {
            return;
        }

        Log::history(
            $ticket_id,
            Ticket::class,
            [self::TIMELINE_LOG_SEARCH_OPTION, '', addslashes($message)],
            0,
            Log::HISTORY_LOG_SIMPLE_MESSAGE
        );
    }

    private static function formatErrorsForLog($errors): string
    {
        if (!is_array($errors) || count($errors) === 0) {
            return t_ebenezerclone('Unknown error');
        }

        $flattened = [];
        array_walk_recursive($errors, static function ($value) use (&$flattened) {
            if ($value !== null && $value !== '') {
                $flattened[] = (string) $value;
            }
        });

        if (count($flattened) === 0) {
            return t_ebenezerclone('Unknown error');
        }

        return implode(' | ', array_unique($flattened));
    }

    private static function isFilledInputValue($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return true;
    }

    private static function buildTitleFromCategory(int $itilcategories_id, string $ticket_identifier = '', string $fallback = ''): string
    {
        $core = self::getCategoryTitleCore($itilcategories_id);
        if ($core === '') {
            return $fallback;
        }

        $ticket_identifier = trim($ticket_identifier);
        if ($ticket_identifier === '') {
            return $core;
        }

        return sprintf('%1$s (%2$s)', $core, $ticket_identifier);
    }

    private static function getCategoryTitleCore(int $itilcategories_id): string
    {
        if ($itilcategories_id <= 0) {
            return '';
        }

        $category = new ITILCategory();
        if (!$category->getFromDB($itilcategories_id)) {
            return '';
        }

        $full_name = trim((string) $category->getField('completename'));
        if ($full_name === '') {
            $full_name = trim((string) $category->getField('name'));
        }
        if ($full_name === '') {
            return '';
        }

        $parts = array_values(array_filter(array_map('trim', explode('>', $full_name)), static fn($part) => $part !== ''));
        if (!count($parts)) {
            return '';
        }

        return implode(' | ', $parts);
    }

    public static function canEditAssignedActors(Ticket $ticket): bool
    {
        $permissions = self::getActorEditPermissions($ticket);
        return !empty($permissions[self::ACTOR_ROLE_REQUESTER])
            || !empty($permissions[self::ACTOR_ROLE_OBSERVER])
            || !empty($permissions[self::ACTOR_ROLE_ASSIGN]);
    }

    public static function getActorEditPermissions(Ticket $ticket): array
    {
        if ($ticket->isNewItem()) {
            return [
                self::ACTOR_ROLE_REQUESTER => true,
                self::ACTOR_ROLE_OBSERVER => true,
                self::ACTOR_ROLE_ASSIGN => true,
            ];
        }

        $status = (int) $ticket->getField('status');
        if (in_array($status, $ticket->getClosedStatusArray(), true)) {
            return [
                self::ACTOR_ROLE_REQUESTER => false,
                self::ACTOR_ROLE_OBSERVER => false,
                self::ACTOR_ROLE_ASSIGN => false,
            ];
        }

        // Global OFF: plugin does not interfere in actor fields.
        if (!PluginEbenezercloneConfig::isGlobalActorFieldsBlocked()) {
            return [
                self::ACTOR_ROLE_REQUESTER => true,
                self::ACTOR_ROLE_OBSERVER => true,
                self::ACTOR_ROLE_ASSIGN => true,
            ];
        }

        $entity_id = (int) $ticket->getField('entities_id');
        return [
            self::ACTOR_ROLE_REQUESTER => self::canEditActorRole($ticket, self::ACTOR_ROLE_REQUESTER, $entity_id),
            self::ACTOR_ROLE_OBSERVER => self::canEditActorRole($ticket, self::ACTOR_ROLE_OBSERVER, $entity_id),
            self::ACTOR_ROLE_ASSIGN => self::canEditActorRole($ticket, self::ACTOR_ROLE_ASSIGN, $entity_id),
        ];
    }

    private static function canEditActorRole(Ticket $ticket, string $role, int $entity_id): bool
    {
        $permission_key = self::getPermissionKeyForActorRole($role);
        if ($permission_key === '') {
            return false;
        }

        $permission = PluginEbenezercloneConfig::hasProfilePermission(
            $permission_key,
            null,
            $entity_id
        );

        // With global actor block enabled, profile matrix acts as explicit ALLOW.
        return $permission === true;
    }

    private static function getPermissionKeyForActorRole(string $role): string
    {
        switch ($role) {
            case self::ACTOR_ROLE_REQUESTER:
                return PluginEbenezercloneConfig::PERMISSION_EDIT_REQUESTER;
            case self::ACTOR_ROLE_OBSERVER:
                return PluginEbenezercloneConfig::PERMISSION_EDIT_OBSERVER;
            case self::ACTOR_ROLE_ASSIGN:
                return PluginEbenezercloneConfig::PERMISSION_EDIT_ASSIGNED;
        }

        return '';
    }

    private static function coreAllowsActorRoleEdit(Ticket $ticket, string $role): bool
    {
        switch ($role) {
            case self::ACTOR_ROLE_ASSIGN:
                return $ticket->canAssign();
            case self::ACTOR_ROLE_REQUESTER:
            case self::ACTOR_ROLE_OBSERVER:
                return $ticket->canUpdateItem();
        }

        return false;
    }

    public static function shouldLockPropertiesByPlugin(Ticket $ticket): bool
    {
        return count(self::getLockedPropertyFieldsByPlugin($ticket)) > 0;
    }

    public static function getLockedPropertyFieldsForCurrentUser(Ticket $ticket): array
    {
        return self::getLockedPropertyFieldsByPlugin($ticket);
    }

    public static function guardAssignedActorsUpdate(Ticket $ticket): bool
    {
        if ($ticket->isNewItem()) {
            return true;
        }

        $has_properties_mutation = self::hasLockedPropertiesMutation($ticket->input ?? []);
        $has_actor_mutation      = self::hasActorMutation($ticket->input ?? []);

        if (!$has_properties_mutation && !$has_actor_mutation) {
            return true;
        }

        if ($has_properties_mutation && self::shouldLockPropertiesByPlugin($ticket)) {
            self::stripLockedPropertiesMutation($ticket->input, $ticket);
        }

        if (!$has_actor_mutation) {
            return true;
        }

        $blocked_actor_roles = self::getBlockedActorRolesForMutation($ticket, $ticket->input ?? []);
        if (!count($blocked_actor_roles)) {
            return true;
        }

        self::stripActorMutation($ticket->input, $blocked_actor_roles);
        self::logPermissionConflict(
            'actor_update_blocked_by_plugin',
            $ticket,
            $ticket->input ?? [],
            ['blocked_actor_roles' => array_values($blocked_actor_roles)]
        );
        Session::addMessageAfterRedirect(
            t_ebenezerclone('You do not have permission to edit actor fields for this ticket.'),
            false,
            ERROR
        );
        return true;
    }

    private static function getBlockedActorRolesForMutation(Ticket $ticket, array $input): array
    {
        if ($ticket->isNewItem()) {
            return [];
        }

        $status = (int) $ticket->getField('status');
        if (in_array($status, $ticket->getClosedStatusArray(), true)) {
            return [
                self::ACTOR_ROLE_REQUESTER,
                self::ACTOR_ROLE_OBSERVER,
                self::ACTOR_ROLE_ASSIGN,
            ];
        }

        // Global OFF: plugin does not interfere in actor fields.
        if (!PluginEbenezercloneConfig::isGlobalActorFieldsBlocked()) {
            return [];
        }

        $actor_roles = self::getActorMutationRoles($input);
        if (!count($actor_roles)) {
            return [];
        }

        $permissions = self::getActorEditPermissions($ticket);
        $blocked = [];
        foreach ($actor_roles as $role) {
            if (empty($permissions[$role])) {
                $blocked[] = $role;
            }
        }

        return $blocked;
    }

    private static function hasLockedPropertiesMutation(array $input): bool
    {
        foreach (self::LOCKED_PROPERTY_FIELDS_AFTER_OPEN as $field) {
            if (array_key_exists($field, $input)) {
                return true;
            }
        }

        return false;
    }

    private static function stripLockedPropertiesMutation(array &$input, Ticket $ticket): void
    {
        $policy_blocked_fields = self::getPolicyBlockedPropertyFieldsByPlugin($ticket);
        $core_allowed_fields = self::getCoreSpecificAllowedPropertyFields($ticket);
        $core_override_fields = array_values(array_intersect(
            $policy_blocked_fields,
            $core_allowed_fields,
            array_keys($input)
        ));

        if (count($core_override_fields)) {
            self::logPermissionConflict(
                'properties_update_allowed_by_core',
                $ticket,
                $input,
                ['core_override_fields' => $core_override_fields]
            );
        }

        $locked_fields = array_values(array_diff($policy_blocked_fields, $core_allowed_fields));
        $blocked_input_fields = array_values(array_intersect($locked_fields, array_keys($input)));
        if (count($blocked_input_fields)) {
            self::logPermissionConflict(
                'properties_update_blocked_by_plugin',
                $ticket,
                $input,
                ['blocked_fields' => $blocked_input_fields]
            );
        }

        foreach ($locked_fields as $field) {
            unset($input[$field]);
        }
    }

    private static function getLockedPropertyFieldsByPlugin(Ticket $ticket): array
    {
        $policy_blocked_fields = self::getPolicyBlockedPropertyFieldsByPlugin($ticket);
        if (!count($policy_blocked_fields)) {
            return [];
        }

        $core_allowed_fields = self::getCoreSpecificAllowedPropertyFields($ticket);
        if (!count($core_allowed_fields)) {
            return $policy_blocked_fields;
        }

        return array_values(array_diff($policy_blocked_fields, $core_allowed_fields));
    }

    private static function getPolicyBlockedPropertyFieldsByPlugin(Ticket $ticket): array
    {
        if ($ticket->isNewItem()) {
            return [];
        }

        $final_statuses = array_merge(
            $ticket->getSolvedStatusArray(),
            $ticket->getClosedStatusArray()
        );
        if (in_array((int) $ticket->getField('status'), $final_statuses, true)) {
            return [];
        }

        $entity_id = (int) $ticket->getField('entities_id');
        $locked_fields = [];
        foreach (self::LOCKED_PROPERTY_FIELDS_AFTER_OPEN as $field) {
            $policy = PluginEbenezercloneConfig::getResolvedTicketPropertyPolicy($field, null, $entity_id);
            if ($policy === PluginEbenezercloneConfig::PROPERTY_POLICY_BLOCK) {
                $locked_fields[] = $field;
            }
        }

        if (!count($locked_fields)) {
            return [];
        }

        return array_values(array_unique($locked_fields));
    }

    private static function getCoreSpecificAllowedPropertyFields(Ticket $ticket): array
    {
        $allowed = [];

        // Preserve native GLPI status transition triggered by assignment flow.
        if (!empty($ticket->input['_from_assignment'])) {
            $allowed[] = 'status';
        }

        // Optional plugin global rule: keep category editable when currently empty.
        if (
            PluginEbenezercloneConfig::isEmptyCategoryEditionAllowed()
            && (int) $ticket->getField('itilcategories_id') <= 0
        ) {
            $allowed[] = 'itilcategories_id';
        }

        // Minimal hardcoded precedence for critical native/core rules.
        // 1) Requester update window (native rule) can allow category update.
        if (method_exists($ticket, 'canRequesterUpdateItem') && $ticket->canRequesterUpdateItem()) {
            $allowed[] = 'itilcategories_id';
        }

        // 2) Core specific right for priority changes.
        if (Session::haveRight(Ticket::$rightname, Ticket::CHANGEPRIORITY)) {
            $allowed[] = 'priority';
        }

        return array_values(array_unique($allowed));
    }

    private static function hasActorMutation(array $input): bool
    {
        return count(self::getActorMutationRoles($input)) > 0;
    }

    private static function getActorMutationRoles(array $input): array
    {
        $roles = [];
        $addRole = static function (string $role) use (&$roles): void {
            if (!in_array($role, $roles, true)) {
                $roles[] = $role;
            }
        };

        $legacy_keys = [
            self::ACTOR_ROLE_REQUESTER => ['_itil_requester', '_users_id_requester', '_groups_id_requester', '_suppliers_id_requester'],
            self::ACTOR_ROLE_OBSERVER  => ['_itil_observer', '_users_id_observer', '_groups_id_observer', '_suppliers_id_observer'],
            self::ACTOR_ROLE_ASSIGN    => ['_itil_assign', '_users_id_assign', '_groups_id_assign', '_suppliers_id_assign'],
        ];
        foreach ($legacy_keys as $role => $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $input)) {
                    $addRole($role);
                    break;
                }
            }
        }

        if (isset($input['_actors']) && is_array($input['_actors'])) {
            foreach ([self::ACTOR_ROLE_REQUESTER, self::ACTOR_ROLE_OBSERVER, self::ACTOR_ROLE_ASSIGN] as $role) {
                if (array_key_exists($role, $input['_actors'])) {
                    $addRole($role);
                }
            }
        }

        if (isset($input['actortype'])) {
            $actor_type = (string) $input['actortype'];
            if ($actor_type === (string) CommonITILActor::REQUESTER) {
                $addRole(self::ACTOR_ROLE_REQUESTER);
            } elseif ($actor_type === (string) CommonITILActor::OBSERVER) {
                $addRole(self::ACTOR_ROLE_OBSERVER);
            } elseif ($actor_type === (string) CommonITILActor::ASSIGN) {
                $addRole(self::ACTOR_ROLE_ASSIGN);
            }
        }

        return $roles;
    }

    private static function stripActorMutation(array &$input, array $blocked_roles): void
    {
        $blocked_roles = array_values(array_unique(array_map('strval', $blocked_roles)));
        if (!count($blocked_roles)) {
            return;
        }

        $legacy_keys = [
            self::ACTOR_ROLE_REQUESTER => ['_itil_requester', '_users_id_requester', '_groups_id_requester', '_suppliers_id_requester', '_users_id_requester_notif'],
            self::ACTOR_ROLE_OBSERVER  => ['_itil_observer', '_users_id_observer', '_groups_id_observer', '_suppliers_id_observer', '_users_id_observer_notif'],
            self::ACTOR_ROLE_ASSIGN    => ['_itil_assign', '_users_id_assign', '_groups_id_assign', '_suppliers_id_assign'],
        ];
        foreach ($blocked_roles as $role) {
            foreach (($legacy_keys[$role] ?? []) as $key) {
                unset($input[$key]);
            }
        }

        if (isset($input['_actors']) && is_array($input['_actors'])) {
            foreach ($blocked_roles as $role) {
                unset($input['_actors'][$role]);
            }
            if (!count($input['_actors'])) {
                unset($input['_actors']);
            }
        }

        if (isset($input['actortype'])) {
            $actortype = (string) $input['actortype'];
            $is_blocked_actortype = (
                ($actortype === (string) CommonITILActor::REQUESTER && in_array(self::ACTOR_ROLE_REQUESTER, $blocked_roles, true))
                || ($actortype === (string) CommonITILActor::OBSERVER && in_array(self::ACTOR_ROLE_OBSERVER, $blocked_roles, true))
                || ($actortype === (string) CommonITILActor::ASSIGN && in_array(self::ACTOR_ROLE_ASSIGN, $blocked_roles, true))
            );
            if ($is_blocked_actortype) {
                unset($input['actortype'], $input['groups_id'], $input['users_id'], $input['suppliers_id'], $input['itemtype'], $input['items_id']);
            }
        }
    }

    private static function getEffectiveFieldModesForCurrentProfile(?int $entity_id = null): array
    {
        return PluginEbenezercloneConfig::getFieldModes();
    }

    private static function coreAllowsTicketUpdate(Ticket $ticket): bool
    {
        return $ticket->can($ticket->getID(), UPDATE);
    }

    private static function logPermissionConflict(string $event, Ticket $ticket, array $input = [], array $extra_context = []): void
    {
        $context = [
            'event'       => $event,
            'ticket_id'   => (int) $ticket->getID(),
            'entity_id'   => (int) $ticket->getField('entities_id'),
            'profile_id'  => (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0),
            'input_keys'  => array_keys($input),
        ];
        if (count($extra_context)) {
            $context = array_merge($context, $extra_context);
        }

        Toolbox::logDebug(
            'EBENEZERCLONE permission conflict',
            $context
        );
    }

    private static function validatePostCloneConsistency(Ticket $source_ticket, int $new_ticket_id, int $expected_category_id): void
    {
        $new_ticket = new Ticket();
        if (!$new_ticket->getFromDB($new_ticket_id)) {
            Toolbox::logDebug('EBENEZERCLONE post-clone validation failed', [
                'reason' => 'clone_not_found',
                'clone_ticket_id' => $new_ticket_id,
                'source_ticket_id' => (int) $source_ticket->getID(),
            ]);
            return;
        }

        $actual_category_id = (int) $new_ticket->getField('itilcategories_id');
        if ($expected_category_id > 0 && $actual_category_id !== $expected_category_id) {
            Toolbox::logDebug('EBENEZERCLONE post-clone validation failed', [
                'reason' => 'category_mismatch',
                'clone_ticket_id' => $new_ticket_id,
                'source_ticket_id' => (int) $source_ticket->getID(),
                'expected_category_id' => $expected_category_id,
                'actual_category_id' => $actual_category_id,
            ]);
        }

        $has_sla_or_ola = (int) $new_ticket->getField('slas_id_ttr') > 0
            || (int) $new_ticket->getField('slas_id_tto') > 0
            || (int) $new_ticket->getField('olas_id_ttr') > 0
            || (int) $new_ticket->getField('olas_id_tto') > 0;

        if (!$has_sla_or_ola) {
            Toolbox::logDebug('EBENEZERCLONE post-clone validation warning', [
                'reason' => 'sla_not_assigned',
                'clone_ticket_id' => $new_ticket_id,
                'source_ticket_id' => (int) $source_ticket->getID(),
                'expected_category_id' => $expected_category_id,
            ]);
        }
    }

}
