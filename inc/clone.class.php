<?php

use Glpi\Event;
use Glpi\Toolbox\Sanitizer;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You cannot access directly to this file');
}

class PluginEbenezercloneClone extends CommonGLPI
{
    public static $rightname = 'plugin_ebenezerclone_clone';
    private const TIMELINE_LOG_SEARCH_OPTION = 21;
    private const TIMELINE_LOG_CLONE_CREATED = 'timeline_log_clone_created';
    private const TIMELINE_LOG_CLONE_SOURCE = 'timeline_log_clone_source';
    private const TIMELINE_LOG_TICKET_LINK = 'timeline_log_ticket_link';
    private const TIMELINE_LOG_FOLLOWUPS = 'timeline_log_followups';
    private const TIMELINE_LOG_ITEMS_COPIED = 'timeline_log_items_copied';
    private const TIMELINE_LOG_ACTORS_COPIED = 'timeline_log_actors_copied';
    private const TIMELINE_LOG_CLONE_FAILURE = 'timeline_log_clone_failure';
    private const CLONED_TITLE_PREFIX = '(Clonado)';

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
            && self::canCloneTicketInCurrentProfile((int) $ticket->getField('entities_id'));
    }

    public static function canCloneTicketInCurrentProfile(?int $entity_id = null): bool
    {
        $resolved_entity_id = self::resolveAuthorizationEntityId($entity_id);
        $profile_id = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
        if ($profile_id <= 0) {
            return false;
        }

        return PluginEbenezercloneConfig::hasProfilePermission(
            PluginEbenezercloneConfig::PERMISSION_CLONE_TICKET,
            $profile_id,
            $resolved_entity_id
        ) === true;
    }

    public static function canUseMassiveCloneActionInCurrentProfile(?int $entity_id = null): bool
    {
        return self::canCloneTicketInCurrentProfile($entity_id);
    }

    public static function canUseTicketCloneActionInCurrentProfile(?int $entity_id = null): bool
    {
        return self::canCloneTicketInCurrentProfile($entity_id);
    }

    private static function resolveAuthorizationEntityId(?int $entity_id): int
    {
        if ($entity_id !== null && $entity_id >= 0) {
            return $entity_id;
        }

        $active_entity_id = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        return $active_entity_id >= 0 ? $active_entity_id : 0;
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
            PluginEbenezercloneConfig::shouldRecalculateTitleFromCategory(),
            (string) ($field_values['name'] ?? '')
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
        bool $should_recalculate_title_from_category,
        string $source_name
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
        $should_recalculate_title_js = $should_recalculate_title_from_category ? 'true' : 'false';

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
var cloneCategoryPendingType$rand = null;
var cloneCategoryPendingReset$rand = false;
var cloneCategoryReadonly$rand = $is_category_readonly_js;
var waitCategoryMessage$rand = $wait_category_message_js;
var mandatoryCategoryMessage$rand = $category_mandatory_message_js;
var mandatoryTypeMessage$rand = $type_mandatory_message_js;
var cloneSourceName$rand = $source_name_js;
var shouldRecalculateTitleFromCategory$rand = $should_recalculate_title_js;

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

var addCloneTitlePrefix$rand = function(title) {
    var value = (title || '').toString();
    var prefix = '(Clonado)';
    if (!value) {
        return prefix;
    }
    if (value.indexOf(prefix) === 0) {
        return value;
    }
    return prefix + ' ' + value;
};

var refreshCloneNamePreview$rand = function() {
    var selectedCategory = parseInt($('#$form_name [name=itilcategories_id]').val(), 10) || 0;
    var shouldAutoGenerate = shouldRecalculateTitleFromCategory$rand && selectedCategory > 0;

    var nextTitle = cloneSourceName$rand;
    if (shouldAutoGenerate) {
        var built = buildCloneNameFromCategory$rand();
        if (built) {
            nextTitle = built;
        }
    }
    nextTitle = addCloneTitlePrefix$rand(nextTitle);

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
    var type = ($('#$form_name [name=type]').val() || '').toString();
    var shouldResetValue = !!resetValue;
    if (cloneCategoryLoading$rand) {
        cloneCategoryPendingType$rand = type;
        cloneCategoryPendingReset$rand = cloneCategoryPendingReset$rand || shouldResetValue;
        return;
    }

    cloneCategoryPendingType$rand = null;
    cloneCategoryPendingReset$rand = false;

    var currentValue = 0;
    if (!shouldResetValue) {
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
            refreshCloneNamePreview$rand();

            if (cloneCategoryPendingType$rand !== null) {
                var pendingType = cloneCategoryPendingType$rand;
                var pendingReset = cloneCategoryPendingReset$rand;
                cloneCategoryPendingType$rand = null;
                cloneCategoryPendingReset$rand = false;

                if (pendingType !== type || pendingReset) {
                    reloadCloneCategory$rand(true);
                }
            }
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
        if (!$ticket->can($ticket->getID(), READ)) {
            Session::addMessageAfterRedirect(__('You do not have permission to perform this action.'), false, ERROR);
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

                if (self::isFilledInputValue($form_value)) {
                    $resolved[$key] = $form_value;
                } elseif (self::isFilledInputValue($clone_value)) {
                    $resolved[$key] = $clone_value;
                } elseif ($key === 'category') {
                    $resolved[$key] = 0;
                } else {
                    $resolved[$key] = $ticket->getField($def['ticket_field']);
                }
            } else {
                $resolved[$key] = $ticket->getField($def['ticket_field']);
            }
        }

        $name = (string) ($resolved['name'] ?? $ticket->getField('name'));
        $original_content = (string) $ticket->getField('content');
        $content = self::normalizeTicketContentForClone($original_content);
        $type = (int) ($resolved['type'] ?? $ticket->getField('type'));
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

        if (PluginEbenezercloneConfig::shouldRecalculateTitleFromCategory() && $itilcategories_id > 0) {
            $computed_name_without_id = self::buildTitleFromCategory($itilcategories_id, '', '');
            if ($computed_name_without_id !== '') {
                $name = $computed_name_without_id;
            }
        }

        $name = self::addClonedTitlePrefix($name);

        $target_category = new ITILCategory();
        if (!$target_category->getFromDB($itilcategories_id) || !$target_category->can($itilcategories_id, READ)) {
            Session::addMessageAfterRedirect(t_ebenezerclone('Selected category is not available.'), false, ERROR);
            return null;
        }

        if (
            ($type === Ticket::INCIDENT_TYPE && empty($target_category->fields['is_incident']))
            || ($type === Ticket::DEMAND_TYPE && empty($target_category->fields['is_request']))
        ) {
            Session::addMessageAfterRedirect(t_ebenezerclone('Selected category is not available.'), false, ERROR);
            return null;
        }

        if (!in_array($type, [Ticket::INCIDENT_TYPE, Ticket::DEMAND_TYPE], true)) {
            Session::addMessageAfterRedirect(t_ebenezerclone('Type is mandatory.'), false, ERROR);
            return null;
        }

        $target_entity = (int) $target_category->getField('entities_id');
        if ($target_entity > 0) {
            $entities_id = $target_entity;
        }
        if (!Session::haveAccessToEntity($entities_id)) {
            Session::addMessageAfterRedirect(__('You do not have permission to perform this action.'), false, ERROR);
            return null;
        }

        $template = $ticket->getITILTemplateToUse(0, $type, $itilcategories_id, $entities_id);
        if ($template && $template->isMandatoryField('itilcategories_id') && $itilcategories_id <= 0) {
            Session::addMessageAfterRedirect(t_ebenezerclone('Category is mandatory.'), false, ERROR);
            return null;
        }

        $new = new Ticket();
        $new->fields['entities_id'] = $entities_id;
        if (
            PluginEbenezercloneConfig::shouldRequireGlpiTicketCreatePermission()
            && !$new->canCreateItem()
        ) {
            Session::addMessageAfterRedirect(__('You do not have permission to perform this action.'), false, ERROR);
            return null;
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
        // Form values are the source of truth for clone target identity fields.
        $new_input['name'] = $name;
        $new_input['type'] = $type;
        $new_input['itilcategories_id'] = $itilcategories_id;
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
                    'event_code'         => 'ticket_add_failed',
                    'plugin_version'      => PLUGIN_EBENEZERCLONE_VERSION,
                    'source_ticket_id'   => (int) $ticket->getID(),
                    'target_entity_id'   => (int) $entities_id,
                    'target_type'        => (int) $type,
                    'target_category_id' => (int) $itilcategories_id,
                ]
            );
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_CLONE_FAILURE,
                (int) $ticket->getID(),
                t_ebenezerclone('Clone failed for this ticket.')
            );
            Session::addMessageAfterRedirect(
                t_ebenezerclone('Failed to clone the ticket.'),
                false,
                ERROR
            );
            return null;
        }

        if ($new->getFromDB($new_id)) {
            $new->fields['content'] = $content;
            if (!$new->updateInDB(['content'], [])) {
                Toolbox::logDebug(
                    'EBENEZERCLONE cloneTicket content preservation failed',
                    [
                        'source_ticket_id' => (int) $ticket->getID(),
                        'target_ticket_id' => (int) $new_id,
                    ]
                );
            }
        }

        $copied_related_ticket_links = 0;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('items')) {
            $copied_related_ticket_links = self::copyRelatedTicketLinks($ticket, $new_id);
        }

        $copied_related_items = 0;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('related_items')) {
            $copied_related_items = self::copyRelatedItems($ticket, $new_id);
        }

        $copied_documents = 0;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('documents')) {
            $copied_documents = self::copyDocuments($ticket, $new_id);
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

        $followups_added = 0;
        if (PluginEbenezercloneConfig::shouldCopyCloneElement('followups')) {
            $followups_added = self::addCloneFollowups($new_id, $ticket->getID());
        }
        self::addHistory(
            $new_id,
            $ticket->getID(),
            $copied_actors,
            $copied_related_ticket_links,
            $copied_related_items,
            $copied_documents,
            $followups_added,
            $link_created
        );

        self::validatePostCloneConsistency($ticket, $new_id, $name, $type, $itilcategories_id);

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

    private static function normalizeTicketContentForClone(string $content): string
    {
        // Match the sanitized format expected by Ticket::add() in GLPI 10.0.20 without manual SQL escaping.
        return Sanitizer::sanitize(Sanitizer::unsanitize($content));
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
                if (!self::canCopyActor(User::class, $user_id)) {
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
                if (!self::canCopyActor(Group::class, $group_id)) {
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
                if (!self::canCopyActor(Supplier::class, $supplier_id)) {
                    continue;
                }
                $input['_suppliers_id_' . $suffix][] = $supplier_id;
                $count++;
            }
        }

        return $count;
    }

    private static function canCopyActor(string $itemtype, int $items_id): bool
    {
        if ($items_id <= 0) {
            return false;
        }

        $actor = getItemForItemtype($itemtype);
        return $actor !== false
            && $actor->getFromDB($items_id)
            && $actor->can($items_id, READ);
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

    private static function copyRelatedTicketLinks(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $source_ticket_id = (int) $ticket->getID();
        $link = new Ticket_Ticket();
        $count = 0;
        foreach (Ticket_Ticket::getLinkedTicketsTo($source_ticket_id, false) as $link_data) {
            $related_ticket_id = (int) ($link_data['tickets_id'] ?? 0);
            $relation_type = (int) ($link_data['link'] ?? 0);
            $related_ticket = new Ticket();
            if (
                $related_ticket_id <= 0
                || !$related_ticket->getFromDB($related_ticket_id)
                || !$related_ticket->can($related_ticket_id, READ)
            ) {
                continue;
            }

            if (isset($link_data['tickets_id_1'])) {
                $tickets_id_1 = (int) $link_data['tickets_id_1'];
                $tickets_id_2 = $new_id;
            } else {
                $tickets_id_1 = $new_id;
                $tickets_id_2 = $related_ticket_id;
            }

            if ($tickets_id_1 === $tickets_id_2) {
                continue;
            }

            if (countElementsInTable(Ticket_Ticket::getTable(), [
                'tickets_id_1' => $tickets_id_1,
                'tickets_id_2' => $tickets_id_2,
                'link' => $relation_type,
            ]) > 0) {
                continue;
            }

            if ($link->add([
                'tickets_id_1' => $tickets_id_1,
                'tickets_id_2' => $tickets_id_2,
                'link' => $relation_type,
            ])) {
                $count++;
            }
        }

        return $count;
    }

    private static function copyRelatedItems(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $source_ticket_id = (int) $ticket->getID();
        $count = 0;
        $item_ticket = new Item_Ticket();
        foreach ($DB->request($item_ticket->getTable(), ['tickets_id' => $source_ticket_id]) as $row) {
            $itemtype = (string) ($row['itemtype'] ?? '');
            $items_id = (int) ($row['items_id'] ?? 0);
            if ($itemtype === '' || $items_id <= 0) {
                continue;
            }

            $related_item = getItemForItemtype($itemtype);
            if (
                !$related_item
                || !$related_item->getFromDB($items_id)
                || !$related_item->can($items_id, READ)
            ) {
                continue;
            }

            if (countElementsInTable(Item_Ticket::getTable(), [
                'tickets_id' => (int) $new_id,
                'itemtype'   => $itemtype,
                'items_id'   => $items_id,
            ]) > 0) {
                continue;
            }

            $item_ticket = new Item_Ticket();
            if ($item_ticket->add([
                'tickets_id'  => (int) $new_id,
                'itemtype'    => $itemtype,
                'items_id'    => $items_id,
                '_no_message' => true,
            ])) {
                Event::log(
                    (int) $new_id,
                    'ticket',
                    4,
                    'tracking',
                    t_ebenezerclone('Related item link copied during clone.')
                );
                $count++;
                continue;
            }

            Toolbox::logDebug(
                'EBENEZERCLONE copyRelatedItems failed',
                [
                    'source_ticket_id' => $source_ticket_id,
                    'target_ticket_id' => (int) $new_id,
                    'itemtype'         => $itemtype,
                    'items_id'         => $items_id,
                ]
            );
        }

        return $count;
    }

    private static function copyDocuments(Ticket $ticket, int $new_id): int
    {
        global $DB;

        $document_item = new Document_Item();
        $count = 0;
        foreach ($DB->request($document_item->getTable(), [
            'itemtype' => Ticket::class,
            'items_id' => (int) $ticket->getID(),
        ]) as $row) {
            $documents_id = (int) ($row['documents_id'] ?? 0);
            $entities_id = (int) ($row['entities_id'] ?? 0);
            $is_recursive = !empty($row['is_recursive']);
            $document = new Document();
            if (
                $documents_id <= 0
                || !$document->getFromDB($documents_id)
                || !$document->can($documents_id, READ)
                || !Session::haveAccessToEntity($entities_id, $is_recursive)
            ) {
                continue;
            }

            if (countElementsInTable($document_item->getTable(), [
                'documents_id' => $documents_id,
                'itemtype'     => Ticket::class,
                'items_id'     => (int) $new_id,
            ]) > 0) {
                continue;
            }

            $input = [
                'documents_id' => $documents_id,
                'itemtype'     => Ticket::class,
                'items_id'     => (int) $new_id,
                'entities_id'  => $entities_id,
                'is_recursive' => $is_recursive ? 1 : 0,
                'users_id'     => (int) ($row['users_id'] ?? 0),
            ];

            $timeline_position = (int) ($row['timeline_position'] ?? 0);
            if ($timeline_position > 0) {
                $input['timeline_position'] = $timeline_position;
            }

            if ($document_item->add($input)) {
                $count++;
            }
        }

        return $count;
    }

    private static function addHistory(
        $new_id,
        $old_id,
        int $copied_actors,
        int $copied_related_ticket_links,
        int $copied_related_items,
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

        if ($copied_related_ticket_links > 0) {
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_ITEMS_COPIED,
                (int) $new_id,
                sprintf(t_ebenezerclone('Copied %1$s related ticket link(s) from source ticket'), $copied_related_ticket_links)
            );
        }

        if ($copied_related_items > 0) {
            self::logTimelineMessageIfEnabled(
                self::TIMELINE_LOG_ITEMS_COPIED,
                (int) $new_id,
                sprintf(t_ebenezerclone('Copied %1$s related item(s) from source ticket'), $copied_related_items)
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
            [self::TIMELINE_LOG_SEARCH_OPTION, '', $message],
            0,
            Log::HISTORY_LOG_SIMPLE_MESSAGE
        );
    }

    private static function getCloneFailureMessages($item): array
    {
        $messages = [];

        if (is_object($item) && method_exists($item, 'getErrors')) {
            try {
                $messages = array_merge($messages, self::flattenMessages($item->getErrors()));
            } catch (\Throwable $e) {
                // Some GLPI objects may expose getErrors() differently; keep the clone failure controlled.
            }
        }

        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
            $session_messages = $_SESSION['MESSAGE_AFTER_REDIRECT'];
            if (defined('ERROR') && is_array($session_messages) && isset($session_messages[ERROR])) {
                $messages = array_merge($messages, self::flattenMessages($session_messages[ERROR]));
            } else {
                $messages = array_merge($messages, self::flattenMessages($session_messages));
            }
        }

        $messages = array_values(array_unique(self::flattenMessages($messages)));
        if (count($messages) === 0) {
            return [
                t_ebenezerclone('Unknown error returned by GLPI. Check php-errors.log and sql-errors.log.'),
            ];
        }

        return $messages;
    }

    private static function flattenMessages($messages): array
    {
        if ($messages === null || $messages === '') {
            return [];
        }

        if (is_array($messages)) {
            if (array_key_exists('message', $messages)) {
                if (array_key_exists('type', $messages) && !self::isErrorMessageType($messages['type'])) {
                    return [];
                }
                return self::flattenMessages($messages['message']);
            }

            $flattened = [];
            foreach ($messages as $key => $value) {
                if (is_string($key) && in_array($key, ['type', 'class', 'date', 'timestamp'], true)) {
                    continue;
                }
                $flattened = array_merge($flattened, self::flattenMessages($value));
            }
            return $flattened;
        }

        if (is_object($messages) || is_resource($messages)) {
            return [];
        }

        $message = trim(strip_tags((string) $messages));
        if ($message === '') {
            return [];
        }

        return [$message];
    }

    private static function isErrorMessageType($type): bool
    {
        if (defined('ERROR') && (string) $type === (string) ERROR) {
            return true;
        }

        return strtolower((string) $type) === 'error';
    }

    private static function formatErrorsForLog($errors): string
    {
        $flattened = self::flattenMessages($errors);
        if (count($flattened) === 0) {
            return t_ebenezerclone('Unknown error');
        }

        return implode(' | ', array_unique($flattened));
    }

    private static function sanitizeCloneInputForLog(array $input, int $depth = 0): array
    {
        $safe = [];

        foreach ($input as $key => $value) {
            $field = (string) $key;

            if (self::isSensitiveCloneLogField($field)) {
                $safe[$field] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                if ($depth >= 2) {
                    $safe[$field] = '[array:' . count($value) . ']';
                    continue;
                }

                $safe[$field] = self::sanitizeCloneInputForLog($value, $depth + 1);
                continue;
            }

            if (is_object($value)) {
                $safe[$field] = '[object:' . get_class($value) . ']';
                continue;
            }

            if (is_resource($value)) {
                $safe[$field] = '[resource]';
                continue;
            }

            if (is_string($value)) {
                $plain = trim(strip_tags($value));
                if ($plain !== $value || strlen($plain) > 120) {
                    $safe[$field] = '[redacted]';
                    continue;
                }

                $safe[$field] = $plain;
                continue;
            }

            $safe[$field] = $value;
        }

        return $safe;
    }

    private static function isSensitiveCloneLogField(string $field): bool
    {
        $field = strtolower($field);
        $sensitive_fields = [
            'name',
            'content',
            '_content',
            '_filename',
            '_tag_filename',
            '_prefix_filename',
            '_files',
            'files',
            'filename',
            'comment',
            'comments',
            'description',
            'text',
            'solution',
            'anexos',
        ];

        if (in_array($field, $sensitive_fields, true)) {
            return true;
        }

        foreach (['content', 'filename', 'comment', 'description', 'solution'] as $needle) {
            if (strpos($field, $needle) !== false) {
                return true;
            }
        }

        return false;
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

    private static function addClonedTitlePrefix(string $name): string
    {
        if ($name === '') {
            return self::CLONED_TITLE_PREFIX;
        }

        if (strncmp($name, self::CLONED_TITLE_PREFIX, strlen(self::CLONED_TITLE_PREFIX)) === 0) {
            return $name;
        }

        return self::CLONED_TITLE_PREFIX . ' ' . $name;
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

    /**
     * Visibility is controlled by plugin configuration (profile/entity/recursive),
     * not by the legacy GLPI profile right of this class.
     */
    public static function canView()
    {
        return true;
    }

    private static function getEffectiveFieldModesForCurrentProfile(?int $entity_id = null): array
    {
        return PluginEbenezercloneConfig::getFieldModes();
    }

    private static function validatePostCloneConsistency(
        Ticket $source_ticket,
        int $new_ticket_id,
        string $expected_name,
        int $expected_type,
        int $expected_category_id
    ): void
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

        $actual_type = (int) $new_ticket->getField('type');
        if ($expected_type > 0 && $actual_type !== $expected_type) {
            Toolbox::logDebug('EBENEZERCLONE post-clone validation failed', [
                'reason' => 'type_mismatch',
                'clone_ticket_id' => $new_ticket_id,
                'source_ticket_id' => (int) $source_ticket->getID(),
                'expected_type' => $expected_type,
                'actual_type' => $actual_type,
                'expected_category_id' => $expected_category_id,
                'actual_category_id' => $actual_category_id,
            ]);
        }

        $actual_name = trim((string) $new_ticket->getField('name'));
        $expected_name = trim($expected_name);
        if ($expected_name !== '' && $actual_name !== $expected_name) {
            Toolbox::logDebug('EBENEZERCLONE post-clone validation failed', [
                'reason' => 'name_mismatch',
                'plugin_version' => PLUGIN_EBENEZERCLONE_VERSION,
                'clone_ticket_id' => $new_ticket_id,
                'source_ticket_id' => (int) $source_ticket->getID(),
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
