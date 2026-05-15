<?php

if (!defined('GLPI_ROOT')) {
    die('Sorry. You cannot access directly to this file');
}

class PluginEbenezercloneConfig extends CommonDBTM
{
    public const MODE_EDITABLE = 'editable';
    public const MODE_READONLY = 'readonly';
    public const MODE_HIDDEN = 'hidden';
    public const CONFIG_KEY_PROFILE_PERMISSION_MATRIX = 'profile_permission_matrix';
    public const CONFIG_KEY_GLOBAL_PERMISSION_POLICIES = 'global_permission_policies';
    public const CONFIG_KEY_PERMISSION_GROUP_TOGGLES = 'permission_group_toggles';
    public const CONFIG_KEY_GLOBAL_BLOCK_CLONE_ACTIONS = 'global_block_clone_actions';
    public const CONFIG_KEY_GLOBAL_BLOCK_ACTOR_FIELDS = 'global_block_actor_fields';
    public const CONFIG_KEY_GLOBAL_BLOCK_ALL_PROPERTIES = 'global_block_all_properties';
    public const CONFIG_KEY_ALLOW_EMPTY_CATEGORY_EDITION = 'allow_empty_category_edition';
    public const CONFIG_KEY_TICKET_PROPERTY_PROFILE_POLICIES = 'ticket_property_profile_policies';
    public const CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES = 'global_clone_copy_policies';
    public const CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE = 'force_assigned_status_on_clone';

    public const PERMISSION_CLONE_TICKET = 'clone_ticket';
    public const PERMISSION_TICKET_CLONE_ACTION = 'ticket_clone_action';
    public const PERMISSION_MASSIVE_CLONE = 'massive_clone';
    public const PERMISSION_EDIT_REQUESTER = 'edit_requester';
    public const PERMISSION_EDIT_OBSERVER = 'edit_observer';
    public const PERMISSION_EDIT_ASSIGNED = 'edit_assigned';
    public const PROPERTY_POLICY_BLOCK = 'block';
    public const PROPERTY_POLICY_ALLOW = 'allow';
    public const PROPERTY_POLICY_IGNORE = 'ignore';
    public const COPY_POLICY_COPY = 'copy';
    public const COPY_POLICY_IGNORE = 'ignore';
    public const PERMISSION_MODE_PROFILE_ONLY = 'profile_only';
    public const PERMISSION_MODE_GLOBAL_PROFILE_OVERRIDE = 'global_profile_override';
    public const PERMISSION_MODE_GLOBAL_DEFAULT_ALLOW_PROFILE_BLOCK = 'global_default_allow_profile_block';
    public const PERMISSION_MODE_GLOBAL_BLOCK_PROFILE_ALLOW = 'global_block_profile_allow';

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() === 'Config') {
            return [1 => t_ebenezerclone('Ebenezer Clone')];
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() === 'Config') {
            $config = new self();
            $config->showFormDisplay();
        }
        return true;
    }

    public static function getDefaults()
    {
        return [
            'field_name_mode' => self::MODE_READONLY,
            'field_type_mode' => self::MODE_EDITABLE,
            'field_category_mode' => self::MODE_EDITABLE,
            'field_content_mode' => self::MODE_EDITABLE,
            'remove_author_default' => 1,
            'timeline_log_clone_created' => 1,
            'timeline_log_clone_source' => 1,
            'timeline_log_ticket_link' => 1,
            'timeline_log_followups' => 0,
            'timeline_log_items_copied' => 0,
            'timeline_log_actors_copied' => 0,
            'timeline_log_clone_failure' => 1,
            self::CONFIG_KEY_GLOBAL_BLOCK_CLONE_ACTIONS => 0,
            self::CONFIG_KEY_GLOBAL_BLOCK_ACTOR_FIELDS => 0,
            self::CONFIG_KEY_GLOBAL_BLOCK_ALL_PROPERTIES => 1,
            self::CONFIG_KEY_ALLOW_EMPTY_CATEGORY_EDITION => 1,
            self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE => 1,
            self::CONFIG_KEY_PROFILE_PERMISSION_MATRIX => '{}',
            self::CONFIG_KEY_GLOBAL_PERMISSION_POLICIES => '{}',
            self::CONFIG_KEY_PERMISSION_GROUP_TOGGLES => '{}',
            self::CONFIG_KEY_TICKET_PROPERTY_PROFILE_POLICIES => '{}',
            self::CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES => '{}',
        ];
    }

    public static function getPermissionDefinitions(): array
    {
        return [
            self::PERMISSION_CLONE_TICKET => [
                'label' => t_ebenezerclone('Clone ticket'),
                'tooltip' => t_ebenezerclone('Allows creating a cloned ticket from the clone tab. If disabled, the profile cannot execute clone in this plugin.'),
            ],
            self::PERMISSION_TICKET_CLONE_ACTION => [
                'label' => t_ebenezerclone('Clone ticket action'),
                'tooltip' => t_ebenezerclone('Allows the Clone action in ticket actions menu. If disabled, clone action is hidden in ticket actions.'),
            ],
            self::PERMISSION_MASSIVE_CLONE => [
                'label' => t_ebenezerclone('Massive clone action'),
                'tooltip' => t_ebenezerclone('Allows the native massive/single clone action in ticket listings. If disabled, clone actions are hidden in massive action menus.'),
            ],
            self::PERMISSION_EDIT_REQUESTER => [
                'label' => t_ebenezerclone('Edit requester'),
                'tooltip' => t_ebenezerclone('Allows editing requester actor in tickets. If disabled, requester changes are blocked for this profile when global actor block is enabled.'),
            ],
            self::PERMISSION_EDIT_OBSERVER => [
                'label' => t_ebenezerclone('Edit observer'),
                'tooltip' => t_ebenezerclone('Allows editing observer actor in tickets. If disabled, observer changes are blocked for this profile when global actor block is enabled.'),
            ],
            self::PERMISSION_EDIT_ASSIGNED => [
                'label' => t_ebenezerclone('Edit assignee'),
                'tooltip' => t_ebenezerclone('Allows editing assigned actor in tickets. If disabled, assignee changes are blocked for this profile when global actor block is enabled.'),
            ],
        ];
    }

    public static function getSupportedPermissionKeys(): array
    {
        return array_keys(self::getPermissionDefinitions());
    }

    public static function getPermissionGroups(): array
    {
        return [
            'clone' => [
                'label' => t_ebenezerclone('Enable cloning'),
                'tooltip' => t_ebenezerclone('Checked: enables clone visibility and usage in this plugin, applying the profile matrix. Unchecked: clone tab and plugin clone controls stay hidden even when the plugin is active.'),
                'permissions' => [
                    self::PERMISSION_CLONE_TICKET,
                    self::PERMISSION_TICKET_CLONE_ACTION,
                    self::PERMISSION_MASSIVE_CLONE,
                ],
            ],
            'assignment' => [
                'label' => t_ebenezerclone('Assignment controls'),
                'tooltip' => t_ebenezerclone('Checked: plugin blocks actor fields by default and profile rules can explicitly allow each actor field. Unchecked: plugin does not enforce actor-field permissions and keeps core/other plugins rules.'),
                'permissions' => [
                    self::PERMISSION_EDIT_REQUESTER,
                    self::PERMISSION_EDIT_OBSERVER,
                    self::PERMISSION_EDIT_ASSIGNED,
                ],
            ],
        ];
    }

    public static function getPermissionModes(): array
    {
        return [
            self::PERMISSION_CLONE_TICKET => self::PERMISSION_MODE_PROFILE_ONLY,
            self::PERMISSION_TICKET_CLONE_ACTION => self::PERMISSION_MODE_PROFILE_ONLY,
            self::PERMISSION_MASSIVE_CLONE => self::PERMISSION_MODE_PROFILE_ONLY,
            self::PERMISSION_EDIT_REQUESTER => self::PERMISSION_MODE_PROFILE_ONLY,
            self::PERMISSION_EDIT_OBSERVER => self::PERMISSION_MODE_PROFILE_ONLY,
            self::PERMISSION_EDIT_ASSIGNED => self::PERMISSION_MODE_PROFILE_ONLY,
        ];
    }

    private static function getPermissionMode(string $permission_key): string
    {
        return self::getPermissionModes()[$permission_key] ?? self::PERMISSION_MODE_GLOBAL_PROFILE_OVERRIDE;
    }

    private static function canPermissionBeConfiguredGlobally(string $permission_key): bool
    {
        return self::getPermissionMode($permission_key) !== self::PERMISSION_MODE_PROFILE_ONLY;
    }

    private static function getPermissionGlobalDefault(string $permission_key): int
    {
        if (self::getPermissionMode($permission_key) === self::PERMISSION_MODE_GLOBAL_DEFAULT_ALLOW_PROFILE_BLOCK) {
            return 1;
        }

        return 0;
    }

    private static function getPermissionGroupMap(): array
    {
        $map = [];
        foreach (self::getPermissionGroups() as $group_key => $group_definition) {
            foreach ((array) ($group_definition['permissions'] ?? []) as $permission_key) {
                $map[(string) $permission_key] = (string) $group_key;
            }
        }

        return $map;
    }

    public static function getAvailableProfiles(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $profiles = [];
        $iterator = $DB->request([
            'FROM'  => 'glpi_profiles',
            'ORDER' => ['name' => 'ASC'],
        ]);

        foreach ($iterator as $row) {
            $profile_id = (int) ($row['id'] ?? 0);
            if ($profile_id <= 0) {
                continue;
            }

            $profiles[$profile_id] = (string) ($row['name'] ?? ('#' . $profile_id));
        }

        return $profiles;
    }

    public static function getProfileAuthorizations(): array
    {
        $scope_data = self::getPermissionScopeData();
        return $scope_data['authorizations'];
    }

    public static function getProfilePermissionMatrix(): array
    {
        $scope_data = self::getPermissionScopeData();
        return $scope_data['permissions'];
    }

    public static function getGlobalPermissionPolicies(): array
    {
        $scope_data = self::getPermissionScopeData();
        return $scope_data['global_policies'];
    }

    public static function getPermissionGroupToggles(): array
    {
        $scope_data = self::getPermissionScopeData();
        return $scope_data['group_toggles'];
    }

    public static function getTicketPropertyDefinitions(): array
    {
        return [
            'date'            => ['label' => __('Opening date')],
            'time_to_resolve' => ['label' => __('Time to resolve')],
            'solvedate'       => ['label' => __('Resolution date')],
            'closedate'       => ['label' => __('Closing date')],
            'type'            => ['label' => _n('Type', 'Types', 1)],
            'itilcategories_id' => ['label' => _n('Category', 'Categories', 1)],
            'status'          => ['label' => __('Status')],
            'requesttypes_id' => ['label' => __('Request source')],
            'urgency'         => ['label' => __('Urgency')],
            'impact'          => ['label' => __('Impact')],
            'priority'        => ['label' => __('Priority')],
            'locations_id'    => ['label' => __('Location')],
            '_contracts_id'   => ['label' => __('Contract')],
            'actiontime'      => ['label' => __('Total duration')],
            'slas_id_ttr'     => ['label' => t_ebenezerclone('SLA Time to resolve')],
            'slas_id_tto'     => ['label' => t_ebenezerclone('SLA Time to own')],
            'olas_id_ttr'     => ['label' => t_ebenezerclone('OLA Time to resolve')],
            'olas_id_tto'     => ['label' => t_ebenezerclone('OLA Time to own')],
            'time_to_own'     => ['label' => __('Time to own')],
        ];
    }

    public static function getTicketPropertyPolicyOptions(): array
    {
        return [
            self::PROPERTY_POLICY_BLOCK  => t_ebenezerclone('Block'),
            self::PROPERTY_POLICY_ALLOW  => t_ebenezerclone('Allow'),
        ];
    }

    public static function getCloneCopyDefinitions(): array
    {
        $field_definitions = [];
        $field_tooltips = self::getCloneCopyFieldTooltips();
        foreach (self::getCloneCopyTicketFields() as $field_key => $field_label) {
            $field_definitions[$field_key] = [
                'label'   => $field_label,
                'kind'    => 'field',
                'section' => self::getCloneCopyFieldSection($field_key),
                'tooltip' => $field_tooltips[$field_key]
                    ?? sprintf(
                        t_ebenezerclone('Checked: copies field %1$s to cloned ticket. Unchecked: does not copy this field.'),
                        $field_label
                    ),
            ];
        }

        return array_merge(
            $field_definitions,
            [
                'actor_requester' => [
                    'label'   => t_ebenezerclone('Requester'),
                    'kind'    => 'field',
                    'section' => 'actors',
                    'tooltip' => t_ebenezerclone('Checked: copies requester actors to the cloned ticket. Unchecked: does not copy requester actors.'),
                ],
                'actor_observer' => [
                    'label'   => t_ebenezerclone('Observer'),
                    'kind'    => 'field',
                    'section' => 'actors',
                    'tooltip' => t_ebenezerclone('Checked: copies observer actors to the cloned ticket. Unchecked: does not copy observer actors.'),
                ],
                'actor_assign' => [
                    'label'   => t_ebenezerclone('Assignee'),
                    'kind'    => 'field',
                    'section' => 'actors',
                    'tooltip' => t_ebenezerclone('Checked: copies assigned actors to the cloned ticket. Unchecked: does not copy assigned actors.'),
                ],
                'items' => [
                    'label'   => t_ebenezerclone('Linked items and assets'),
                    'kind'    => 'component',
                    'section' => 'relationships',
                    'tooltip' => t_ebenezerclone('Checked: copies linked items and assets from the source ticket. Unchecked: does not copy linked items and assets.'),
                ],
                'documents' => [
                    'label'   => t_ebenezerclone('Ticket documents and attachments'),
                    'kind'    => 'component',
                    'section' => 'documents',
                    'tooltip' => t_ebenezerclone('Checked: copies ticket-level document and attachment links to the cloned ticket. Unchecked: does not copy them.'),
                ],
                'followup_documents' => [
                    'label'   => t_ebenezerclone('Follow-up attachments'),
                    'kind'    => 'component',
                    'section' => 'documents',
                    'tooltip' => t_ebenezerclone('Checked: copies attachments linked to copied follow-ups. Unchecked: does not copy follow-up attachments.'),
                ],
                'task_documents' => [
                    'label'   => t_ebenezerclone('Task attachments'),
                    'kind'    => 'component',
                    'section' => 'documents',
                    'tooltip' => t_ebenezerclone('Checked: copies attachments linked to copied tasks. Unchecked: does not copy task attachments.'),
                ],
                'solution_documents' => [
                    'label'   => t_ebenezerclone('Solution attachments'),
                    'kind'    => 'component',
                    'section' => 'documents',
                    'tooltip' => t_ebenezerclone('Checked: copies attachments linked to copied solutions. Unchecked: does not copy solution attachments.'),
                ],
                'validation_documents' => [
                    'label'   => t_ebenezerclone('Approval attachments'),
                    'kind'    => 'component',
                    'section' => 'documents',
                    'tooltip' => t_ebenezerclone('Checked: copies attachments linked to copied approvals. Unchecked: does not copy approval attachments.'),
                ],
                'ticket_link' => [
                    'label'   => t_ebenezerclone('Link source and cloned tickets'),
                    'kind'    => 'component',
                    'section' => 'relationships',
                    'tooltip' => t_ebenezerclone('Checked: creates a direct link between source and cloned tickets. Unchecked: does not create this link.'),
                ],
                'ticket_relations' => [
                    'label'   => t_ebenezerclone('Existing related tickets'),
                    'kind'    => 'component',
                    'section' => 'relationships',
                    'tooltip' => t_ebenezerclone('Checked: copies the ticket relationships that already exist on the source ticket. Unchecked: does not copy those relationships.'),
                ],
                'contracts' => [
                    'label'   => t_ebenezerclone('Contracts'),
                    'kind'    => 'component',
                    'section' => 'relationships',
                    'tooltip' => t_ebenezerclone('Checked: copies contracts linked to the source ticket. Unchecked: does not copy contracts.'),
                ],
                'projects' => [
                    'label'   => t_ebenezerclone('Projects'),
                    'kind'    => 'component',
                    'section' => 'relationships',
                    'tooltip' => t_ebenezerclone('Checked: copies projects linked to the source ticket. Unchecked: does not copy projects.'),
                ],
                'problem_links' => [
                    'label'   => t_ebenezerclone('Problems'),
                    'kind'    => 'component',
                    'section' => 'relationships',
                    'tooltip' => t_ebenezerclone('Checked: copies problems linked to the source ticket. Unchecked: does not copy problem links.'),
                ],
                'change_links' => [
                    'label'   => t_ebenezerclone('Changes'),
                    'kind'    => 'component',
                    'section' => 'relationships',
                    'tooltip' => t_ebenezerclone('Checked: copies changes linked to the source ticket. Unchecked: does not copy change links.'),
                ],
                'followups' => [
                    'label'   => t_ebenezerclone('Informational clone comments'),
                    'kind'    => 'component',
                    'section' => 'timeline',
                    'tooltip' => t_ebenezerclone('Checked: creates informational comments linking source and clone during cloning. Unchecked: does not create these comments.'),
                ],
                'followup_history' => [
                    'label'   => t_ebenezerclone('Ticket follow-ups and comments'),
                    'kind'    => 'component',
                    'section' => 'timeline',
                    'tooltip' => t_ebenezerclone('Checked: copies follow-ups and comments visible on the source ticket. Unchecked: does not copy them.'),
                ],
                'tasks' => [
                    'label'   => t_ebenezerclone('Tasks'),
                    'kind'    => 'component',
                    'section' => 'timeline',
                    'tooltip' => t_ebenezerclone('Checked: copies tasks from the source ticket. Unchecked: does not copy tasks.'),
                ],
                'solutions' => [
                    'label'   => t_ebenezerclone('Solutions'),
                    'kind'    => 'component',
                    'section' => 'timeline',
                    'tooltip' => t_ebenezerclone('Checked: copies solutions from the source ticket. Unchecked: does not copy solutions.'),
                ],
                'validations' => [
                    'label'   => t_ebenezerclone('Approvals'),
                    'kind'    => 'component',
                    'section' => 'approvals',
                    'tooltip' => t_ebenezerclone('Checked: copies approval requests and answers from the source ticket. Unchecked: does not copy approvals.'),
                ],
                'satisfaction' => [
                    'label'   => t_ebenezerclone('Satisfaction survey'),
                    'kind'    => 'component',
                    'section' => 'timeline',
                    'tooltip' => t_ebenezerclone('Checked: copies satisfaction survey data from the source ticket. Unchecked: does not copy satisfaction data.'),
                ],
            ]
        );
    }

    public static function getCloneCopySections(): array
    {
        return [
            'ticket' => ['label' => t_ebenezerclone('Ticket')],
            'actors' => ['label' => t_ebenezerclone('Actors')],
            'stats' => ['label' => t_ebenezerclone('Statistics and automatic dates')],
            'approvals' => ['label' => t_ebenezerclone('Approvals')],
            'relationships' => ['label' => t_ebenezerclone('Changes, problems, projects and relationships')],
            'timeline' => ['label' => t_ebenezerclone('Functional timeline')],
            'documents' => ['label' => t_ebenezerclone('Documents and attachments')],
        ];
    }

    public static function getCloneCopyPolicyOptions(): array
    {
        return [
            self::COPY_POLICY_COPY => t_ebenezerclone('Copy'),
            self::COPY_POLICY_IGNORE => t_ebenezerclone('Ignore'),
        ];
    }

    public static function getGlobalCloneCopyPolicies(): array
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return self::normalizeGlobalCloneCopyPolicies(
            self::decodePermissionMatrix((string) ($config[self::CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES] ?? '{}'))
        );
    }

    public static function shouldCopyCloneElement(string $element_key): bool
    {
        $policies = self::getGlobalCloneCopyPolicies();
        $policy = (string) ($policies[$element_key] ?? self::COPY_POLICY_COPY);
        return $policy !== self::COPY_POLICY_IGNORE;
    }

    public static function getCloneCopyTicketFieldKeys(): array
    {
        return array_keys(self::getCloneCopyTicketFields());
    }

    private static function getCloneCopyTicketFields(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ticket = new Ticket();
        $table = (string) $ticket->getTable();
        $search_labels_map = self::getCloneCopySearchOptionLabelsMap();
        $fields = [];

        foreach ($DB->request("SHOW COLUMNS FROM `$table`") as $row) {
            $field = (string) ($row['Field'] ?? '');
            if ($field === '' || in_array($field, self::getCloneCopyTicketFieldExclusions(), true)) {
                continue;
            }

            $fields[$field] = $search_labels_map[$field] ?? self::formatFieldKeyForDisplay($field);
        }

        ksort($fields);
        return $fields;
    }

    private static function getCloneCopyFieldSection(string $field_key): string
    {
        $approval_fields = [
            'global_validation',
            'validation_percent',
        ];
        $stats_fields = [
            'actiontime',
            'assign_delay_stat',
            'begin_waiting_date',
            'close_delay_stat',
            'closedate',
            'date',
            'internal_time_to_own',
            'internal_time_to_resolve',
            'ola_tto_begin_date',
            'ola_ttr_begin_date',
            'ola_waiting_duration',
            'olalevels_id_ttr',
            'olas_id_tto',
            'olas_id_ttr',
            'sla_waiting_duration',
            'slalevels_id_ttr',
            'slas_id_tto',
            'slas_id_ttr',
            'solve_delay_stat',
            'solvedate',
            'takeintoaccountdate',
            'time_to_own',
            'time_to_resolve',
            'waiting_duration',
        ];

        if (in_array($field_key, $approval_fields, true)) {
            return 'approvals';
        }

        if (in_array($field_key, $stats_fields, true)) {
            return 'stats';
        }

        return 'ticket';
    }

    private static function getCloneCopyTicketFieldExclusions(): array
    {
        return [
            'id',
            'date_mod',
            'date_creation',
            'users_id_lastupdater',
        ];
    }
    private static function getCloneCopySearchOptionLabelsMap(): array
    {
        $labels = [];
        $ticket = new Ticket();
        $search_options = [];
        try {
            $search_options = (array) $ticket->rawSearchOptions();
        } catch (\Throwable $e) {
            return $labels;
        }

        foreach ($search_options as $search_option) {
            if (!is_array($search_option)) {
                continue;
            }

            $field = trim((string) ($search_option['field'] ?? ''));
            $name = trim((string) ($search_option['name'] ?? ''));
            if ($field === '' || $name === '') {
                continue;
            }

            if (!array_key_exists($field, $labels)) {
                $labels[$field] = $name;
            }
        }

        return $labels;
    }

    private static function formatFieldKeyForDisplay(string $field_key): string
    {
        return t_ebenezerclone(trim($field_key));
    }

    private static function getCloneCopyFieldTooltips(): array
    {
        return [
            'actiontime'             => t_ebenezerclone('Informs the total time recorded in the ticket.'),
            'begin_waiting_date'     => t_ebenezerclone('Indicates when the ticket entered waiting status.'),
            'close_delay_stat'       => t_ebenezerclone('Indicates the time when the ticket was closed.'),
            'closedate'              => t_ebenezerclone('Indicates the date when the ticket was closed.'),
            'content'                => t_ebenezerclone('Defines the detailed description of the ticket.'),
            'date'                   => t_ebenezerclone('Indicates when the ticket was opened.'),
            'entities_id'            => t_ebenezerclone('Defines the entity responsible for the ticket.'),
            'global_validation'      => t_ebenezerclone('Indicates the approval status of the ticket.'),
            'impact'                 => t_ebenezerclone('Defines the impact of the ticket.'),
            'internal_time_to_own'   => t_ebenezerclone('Defines the internal deadline to take ownership of the ticket.'),
            'internal_time_to_resolve' => t_ebenezerclone('Defines the internal deadline to resolve the ticket.'),
            'is_deleted'             => t_ebenezerclone('Indicates whether the ticket is marked as deleted.'),
            'itilcategories_id'      => t_ebenezerclone('Defines the category assigned to the ticket.'),
            'locations_id'           => t_ebenezerclone('Defines the location associated with the ticket.'),
            'name'                   => t_ebenezerclone('Defines the title of the ticket.'),
            'ola_tto_begin_date'     => t_ebenezerclone('Indicates when OLA timing started for taking ownership of the ticket.'),
            'ola_ttr_begin_date'     => t_ebenezerclone('Indicates when OLA timing started for resolving the ticket.'),
            'ola_waiting_duration'   => t_ebenezerclone('Informs the waiting time considered in the OLA calculation.'),
            'olalevels_id_ttr'       => t_ebenezerclone('Defines the OLA level applied to ticket resolution.'),
            'olas_id_tto'            => t_ebenezerclone('Defines the OLA applied to the deadline for taking ownership of the ticket.'),
            'olas_id_ttr'            => t_ebenezerclone('Defines the OLA applied to the deadline for resolving the ticket.'),
            'priority'               => t_ebenezerclone('Defines the priority of the ticket.'),
            'requesttypes_id'        => t_ebenezerclone('Defines the source through which the ticket was created.'),
            'sla_waiting_duration'   => t_ebenezerclone('Informs the waiting time considered in the SLA calculation.'),
            'slalevels_id_ttr'       => t_ebenezerclone('Defines the SLA level applied to ticket resolution.'),
            'slas_id_tto'            => t_ebenezerclone('Defines the SLA applied to the deadline for taking ownership of the ticket.'),
            'slas_id_ttr'            => t_ebenezerclone('Defines the SLA applied to the deadline for resolving the ticket.'),
            'solve_delay_stat'       => t_ebenezerclone('Informs the elapsed time until the ticket was resolved.'),
            'solvedate'              => t_ebenezerclone('Indicates when the ticket was resolved.'),
            'status'                 => t_ebenezerclone('Indicates the current status of the ticket.'),
            'assign_delay_stat'      => t_ebenezerclone('Informs the elapsed time until the ticket was assigned.'),
            'takeintoaccountdate'    => t_ebenezerclone('Indicates when the ticket started to be effectively handled.'),
            'time_to_own'            => t_ebenezerclone('Defines the deadline for taking ownership of the ticket.'),
            'time_to_resolve'        => t_ebenezerclone('Defines the deadline for resolving the ticket.'),
            'type'                   => t_ebenezerclone('Defines the type of the ticket.'),
            'urgency'                => t_ebenezerclone('Defines the urgency of the ticket.'),
            'users_id_recipient'     => t_ebenezerclone('Defines the main recipient of the ticket.'),
            'validation_percent'     => t_ebenezerclone('Indicates that the ticket requires at least one approval.'),
            'waiting_duration'       => t_ebenezerclone('Informs the total time the ticket remained in waiting status.'),
        ];
    }

    public static function isGlobalTicketPropertiesBlockingEnabled(): bool
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return !empty($config[self::CONFIG_KEY_GLOBAL_BLOCK_ALL_PROPERTIES]);
    }

    public static function isEmptyCategoryEditionAllowed(): bool
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return !empty($config[self::CONFIG_KEY_ALLOW_EMPTY_CATEGORY_EDITION]);
    }

    public static function shouldForceAssignedStatusOnClone(): bool
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return !empty($config[self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE]);
    }

    public static function getTicketPropertyProfilePolicies(): array
    {
        $scope_data = self::getPermissionScopeData();
        return $scope_data['ticket_property_policies'];
    }

    public static function getResolvedTicketPropertyPolicy(
        string $property_key,
        ?int $profile_id = null,
        ?int $entity_id = null
    ): ?string {
        if (!array_key_exists($property_key, self::getTicketPropertyDefinitions())) {
            return null;
        }

        if ($profile_id === null) {
            $profile_id = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
        }
        $global_default_policy = self::isGlobalTicketPropertiesBlockingEnabled()
            ? self::PROPERTY_POLICY_BLOCK
            : self::PROPERTY_POLICY_ALLOW;
        if ($profile_id <= 0) {
            return $global_default_policy;
        }

        if ($entity_id === null) {
            $entity_id = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }
        if ($entity_id < 0) {
            $entity_id = 0;
        }

        $decision = $global_default_policy;
        $authorizations = self::getProfileAuthorizations();
        $policies = self::getTicketPropertyProfilePolicies();

        $matched_authorizations = [];
        foreach ($authorizations as $authorization_id => $authorization) {
            if ((int) ($authorization['profiles_id'] ?? 0) !== $profile_id) {
                continue;
            }
            $specificity = self::getAuthorizationSpecificity($authorization, $entity_id);
            if ($specificity === null) {
                continue;
            }
            $matched_authorizations[] = [
                'authorization_id' => (string) $authorization_id,
                'specificity'      => $specificity,
            ];
        }

        usort($matched_authorizations, static function (array $a, array $b): int {
            return $a['specificity'] <=> $b['specificity'];
        });

        foreach ($matched_authorizations as $matched_authorization) {
            $authorization_id = (string) ($matched_authorization['authorization_id'] ?? '');
            if ($authorization_id === '') {
                continue;
            }
            if (!isset($policies[$authorization_id]) || !array_key_exists($property_key, $policies[$authorization_id])) {
                continue;
            }
            $decision = !empty($policies[$authorization_id][$property_key])
                ? self::PROPERTY_POLICY_BLOCK
                : self::PROPERTY_POLICY_ALLOW;
        }

        return $decision;
    }

    public static function hasProfilePermission(string $permission_key, ?int $profile_id = null, ?int $entity_id = null): ?bool
    {
        if (!in_array($permission_key, self::getSupportedPermissionKeys(), true)) {
            return null;
        }

        if ($profile_id === null) {
            $profile_id = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
        }
        if ($profile_id <= 0) {
            return null;
        }

        if ($entity_id === null) {
            $entity_id = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }
        if ($entity_id < 0) {
            $entity_id = 0;
        }

        $permission_mode = self::getPermissionMode($permission_key);
        $group_map = self::getPermissionGroupMap();
        $permission_group_key = $group_map[$permission_key] ?? null;

        // Global layer toggle has precedence over every profile-level rule.
        $group_toggles = self::getPermissionGroupToggles();
        if ($permission_group_key !== null && empty($group_toggles[$permission_group_key])) {
            // Layer disabled: plugin does not enforce profile matrix for this permission.
            return null;
        }

        $decision = null;

        if ($permission_mode !== self::PERMISSION_MODE_PROFILE_ONLY) {
            $global_policies = self::getGlobalPermissionPolicies();
            if (array_key_exists($permission_key, $global_policies)) {
                if ($permission_mode === self::PERMISSION_MODE_GLOBAL_BLOCK_PROFILE_ALLOW) {
                    // In this mode, checked global checkbox means BLOCK.
                    $decision = !empty($global_policies[$permission_key]) ? false : true;
                } else {
                    $decision = !empty($global_policies[$permission_key]);
                }
            }
        }

        $authorizations = self::getProfileAuthorizations();
        $matrix = self::getProfilePermissionMatrix();

        $matched_authorizations = [];
        foreach ($authorizations as $authorization_id => $authorization) {
            if ((int) ($authorization['profiles_id'] ?? 0) !== $profile_id) {
                continue;
            }
            $specificity = self::getAuthorizationSpecificity($authorization, $entity_id);
            if ($specificity === null) {
                continue;
            }
            $matched_authorizations[] = [
                'authorization_id' => (string) $authorization_id,
                'specificity'      => $specificity,
            ];
        }

        usort($matched_authorizations, static function (array $a, array $b): int {
            return $a['specificity'] <=> $b['specificity'];
        });

        foreach ($matched_authorizations as $matched_authorization) {
            $authorization_id = (string) ($matched_authorization['authorization_id'] ?? '');
            if ($authorization_id === '' || !array_key_exists($permission_key, $matrix[$authorization_id] ?? [])) {
                continue;
            }
            $profile_value = !empty($matrix[$authorization_id][$permission_key]);
            if ($permission_mode === self::PERMISSION_MODE_GLOBAL_BLOCK_PROFILE_ALLOW) {
                // Profile matrix works as ALLOW-list only for this permission.
                if ($profile_value) {
                    $decision = true;
                }
                continue;
            }
            $decision = $profile_value;
        }
        return is_bool($decision) ? $decision : null;
    }

    private static function decodePermissionMatrix(string $raw): array
    {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function getPermissionScopeData(): array
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        $scope_raw = self::decodePermissionMatrix((string) ($config[self::CONFIG_KEY_PROFILE_PERMISSION_MATRIX] ?? '{}'));
        $scope_raw['global_policies'] = self::decodePermissionMatrix(
            (string) ($config[self::CONFIG_KEY_GLOBAL_PERMISSION_POLICIES] ?? '{}')
        );
        $scope_raw['group_toggles'] = self::decodePermissionMatrix(
            (string) ($config[self::CONFIG_KEY_PERMISSION_GROUP_TOGGLES] ?? '{}')
        );
        $scope_raw['ticket_property_policies'] = self::decodePermissionMatrix(
            (string) ($config[self::CONFIG_KEY_TICKET_PROPERTY_PROFILE_POLICIES] ?? '{}')
        );
        $scope_raw['global_copy_policies'] = self::decodePermissionMatrix(
            (string) ($config[self::CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES] ?? '{}')
        );

        return self::normalizePermissionScopeData($scope_raw);
    }

    private static function normalizePermissionScopeData(array $raw): array
    {
        if (isset($raw['authorizations']) || isset($raw['permissions'])) {
            return [
                'authorizations'   => self::normalizeAuthorizations($raw['authorizations'] ?? []),
                'permissions'      => self::normalizePermissionMatrix($raw['permissions'] ?? []),
                'global_policies'  => self::normalizeGlobalPermissionPolicies($raw['global_policies'] ?? []),
                'group_toggles'    => self::normalizePermissionGroupToggles($raw['group_toggles'] ?? []),
                'ticket_property_policies' => self::normalizeTicketPropertyProfilePolicies($raw['ticket_property_policies'] ?? []),
                'global_copy_policies' => self::normalizeGlobalCloneCopyPolicies($raw['global_copy_policies'] ?? []),
            ];
        }

        // Backward compatibility: old format was [profiles_id => [permission => 0/1]]
        $legacy_raw = $raw;
        unset($legacy_raw['global_policies'], $legacy_raw['group_toggles'], $legacy_raw['ticket_property_policies'], $legacy_raw['global_copy_policies']);
        $legacy_permissions = self::normalizePermissionMatrix($legacy_raw);
        $legacy_authorizations = [];
        $migrated_permissions = [];
        foreach ($legacy_permissions as $profile_id => $permissions) {
            $authorization_id = self::buildAuthorizationId((int) $profile_id, 0, 1);
            $legacy_authorizations[$authorization_id] = [
                'profiles_id'  => (int) $profile_id,
                'entities_id'  => 0,
                'is_recursive' => 1,
            ];
            $migrated_permissions[$authorization_id] = $permissions;
        }

        return [
            'authorizations'   => $legacy_authorizations,
            'permissions'      => $migrated_permissions,
            'global_policies'  => self::normalizeGlobalPermissionPolicies([]),
            'group_toggles'    => self::normalizePermissionGroupToggles([]),
            'ticket_property_policies' => self::normalizeTicketPropertyProfilePolicies([]),
            'global_copy_policies' => self::normalizeGlobalCloneCopyPolicies([]),
        ];
    }

    private static function normalizeAuthorizations(array $authorizations): array
    {
        $normalized = [];
        foreach ($authorizations as $authorization_id => $authorization) {
            if (!is_array($authorization)) {
                continue;
            }

            $profiles_id = (int) ($authorization['profiles_id'] ?? 0);
            $entities_id = (int) ($authorization['entities_id'] ?? 0);
            $is_recursive = !empty($authorization['is_recursive']) ? 1 : 0;
            if ($profiles_id <= 0 || $entities_id < 0) {
                continue;
            }

            $normalized_id = self::buildAuthorizationId($profiles_id, $entities_id, $is_recursive);
            $normalized[$normalized_id] = [
                'profiles_id'  => $profiles_id,
                'entities_id'  => $entities_id,
                'is_recursive' => $is_recursive,
            ];
        }

        return $normalized;
    }

    private static function normalizePermissionMatrix(array $matrix): array
    {
        $supported = self::getSupportedPermissionKeys();
        $normalized = [];

        foreach ($matrix as $authorization_id => $permissions) {
            $authorization_key = trim((string) $authorization_id);
            if ($authorization_key === '' || !is_array($permissions)) {
                continue;
            }

            $normalized[$authorization_key] = [];
            foreach ($supported as $permission_key) {
                $raw_permission = $permissions[$permission_key] ?? null;
                $has_explicit_value = self::isLegacyPermissionValueDefined($permissions, $permission_key);
                if (!$has_explicit_value && $permission_key === self::PERMISSION_EDIT_ASSIGNED) {
                    // Backward compatibility: merge legacy assigned-group/assigned-technician flags
                    // into the new single "Edit assignee" permission.
                    $raw_permission = (
                        !empty($permissions['edit_assigned_group'])
                        || !empty($permissions['edit_assigned_technician'])
                    ) ? 1 : 0;
                    $has_explicit_value = (
                        self::isLegacyPermissionValueDefined($permissions, 'edit_assigned_group')
                        || self::isLegacyPermissionValueDefined($permissions, 'edit_assigned_technician')
                    );
                }
                if (is_array($raw_permission)) {
                    // Backward compatibility: old payload had "enabled + allow".
                    // New payload is a single boolean checkbox: checked=allow, unchecked=block.
                    if (array_key_exists('allow', $raw_permission)) {
                        $value = !empty($raw_permission['allow']) ? 1 : 0;
                    } else {
                        $value = !empty($raw_permission) ? 1 : 0;
                    }
                } else {
                    $value = $has_explicit_value
                        ? (!empty($raw_permission) ? 1 : 0)
                        : 0;
                }

                $normalized[$authorization_key][$permission_key] = $value;
            }
        }

        return $normalized;
    }

    private static function normalizeGlobalPermissionPolicies(array $policies): array
    {
        $supported = self::getSupportedPermissionKeys();
        $normalized = [];
        foreach ($supported as $permission_key) {
            $raw_policy = $policies[$permission_key] ?? null;
            if (!self::canPermissionBeConfiguredGlobally($permission_key)) {
                $normalized[$permission_key] = 0;
                continue;
            }
            if (is_array($raw_policy)) {
                if (array_key_exists('allow', $raw_policy)) {
                    $value = !empty($raw_policy['allow']) ? 1 : 0;
                } else {
                    $value = !empty($raw_policy) ? 1 : 0;
                }
            } else {
                $value = self::isLegacyPermissionValueDefined($policies, $permission_key)
                    ? (!empty($raw_policy) ? 1 : 0)
                    : self::getPermissionGlobalDefault($permission_key);
            }

            $normalized[$permission_key] = $value;
        }

        return $normalized;
    }

    private static function normalizePermissionGroupToggles(array $group_toggles): array
    {
        $normalized = [];
        foreach (self::getPermissionGroups() as $group_key => $group_definition) {
            $normalized[(string) $group_key] = array_key_exists($group_key, $group_toggles)
                ? (!empty($group_toggles[$group_key]) ? 1 : 0)
                : 1;
        }

        return $normalized;
    }

    private static function normalizeTicketPropertyProfilePolicies(array $policies): array
    {
        $definitions = self::getTicketPropertyDefinitions();
        $normalized = [];

        foreach ($policies as $authorization_id => $property_policies) {
            $authorization_key = trim((string) $authorization_id);
            if ($authorization_key === '' || !is_array($property_policies)) {
                continue;
            }

            $normalized[$authorization_key] = [];
            foreach ($definitions as $property_key => $definition) {
                $normalized[$authorization_key][$property_key] = self::normalizeTicketPropertyPolicyValue(
                    $property_policies[$property_key] ?? 0
                );
            }
        }

        return $normalized;
    }

    private static function normalizeTicketPropertyPolicyValue($value): int
    {
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (
                $normalized === self::PROPERTY_POLICY_BLOCK
                || $normalized === '1'
                || $normalized === 'true'
                || $normalized === 'on'
                || $normalized === 'yes'
            ) {
                return 1;
            }

            if (
                $normalized === self::PROPERTY_POLICY_ALLOW
                || $normalized === self::PROPERTY_POLICY_IGNORE
                || $normalized === '0'
                || $normalized === 'false'
                || $normalized === 'off'
                || $normalized === 'no'
                || $normalized === ''
            ) {
                return 0;
            }
        }

        return !empty($value) ? 1 : 0;
    }

    private static function normalizeGlobalCloneCopyPolicies(array $policies): array
    {
        $definitions = self::getCloneCopyDefinitions();
        $allowed_states = array_keys(self::getCloneCopyPolicyOptions());
        $normalized = [];

        foreach ($definitions as $policy_key => $definition) {
            $raw_state = (string) ($policies[$policy_key] ?? self::COPY_POLICY_COPY);
            $normalized[$policy_key] = in_array($raw_state, $allowed_states, true)
                ? $raw_state
                : self::COPY_POLICY_COPY;
        }

        return $normalized;
    }

    private static function isLegacyPermissionValueDefined(array $source, string $key): bool
    {
        return array_key_exists($key, $source);
    }

    private static function buildAuthorizationId(int $profiles_id, int $entities_id, int $is_recursive): string
    {
        return sprintf('p%1$d_e%2$d_r%3$d', $profiles_id, $entities_id, $is_recursive ? 1 : 0);
    }

    private static function isAuthorizationMatchingEntity(array $authorization, int $entity_id): bool
    {
        $authorization_entity = (int) ($authorization['entities_id'] ?? 0);
        if ($authorization_entity === 0) {
            return true;
        }

        if ($authorization_entity === $entity_id) {
            return true;
        }

        if (empty($authorization['is_recursive'])) {
            return false;
        }

        $sons = array_map('intval', getSonsOf('glpi_entities', $authorization_entity));
        return in_array($entity_id, $sons, true);
    }

    private static function getAuthorizationSpecificity(array $authorization, int $entity_id): ?int
    {
        $authorization_entity = (int) ($authorization['entities_id'] ?? 0);
        $is_recursive = !empty($authorization['is_recursive']);

        if ($authorization_entity === 0) {
            return 1000;
        }

        if ($authorization_entity === $entity_id) {
            return 3000;
        }

        if (!$is_recursive) {
            return null;
        }

        $sons = array_map('intval', getSonsOf('glpi_entities', $authorization_entity));
        if (!in_array($entity_id, $sons, true)) {
            return null;
        }

        return 2000 + $authorization_entity;
    }

    public static function getTimelineLogDefinitions()
    {
        return [
            'timeline_log_clone_created' => t_ebenezerclone('Log clone creation on cloned ticket'),
            'timeline_log_clone_source' => t_ebenezerclone('Log clone reference on source ticket'),
            'timeline_log_ticket_link' => t_ebenezerclone('Log ticket link creation'),
            'timeline_log_followups' => t_ebenezerclone('Log informational followups created by plugin'),
            'timeline_log_items_copied' => t_ebenezerclone('Log copied linked items'),
            'timeline_log_actors_copied' => t_ebenezerclone('Log copied actors'),
            'timeline_log_clone_failure' => t_ebenezerclone('Log clone failure on source ticket'),
        ];
    }

    public static function getTimelineLogTooltips(): array
    {
        return [
            'timeline_log_clone_created' => t_ebenezerclone('Checked: logs clone creation on the cloned ticket timeline. Unchecked: does not create this timeline log on cloned ticket.'),
            'timeline_log_clone_source' => t_ebenezerclone('Checked: logs clone reference on the source ticket timeline. Unchecked: does not create this timeline log on source ticket.'),
            'timeline_log_ticket_link' => t_ebenezerclone('Checked: logs creation of link between source and cloned tickets. Unchecked: does not create timeline log for ticket link.'),
            'timeline_log_followups' => t_ebenezerclone('Checked: logs informational activities created by plugin during cloning. Unchecked: does not create timeline log for informational activities.'),
            'timeline_log_items_copied' => t_ebenezerclone('Checked: logs copied linked items and copied document links. Unchecked: does not create timeline log for copied items/documents.'),
            'timeline_log_actors_copied' => t_ebenezerclone('Checked: logs copied actors from source ticket. Unchecked: does not create timeline log for copied actors.'),
            'timeline_log_clone_failure' => t_ebenezerclone('Checked: logs clone failure on source ticket timeline. Unchecked: does not create timeline log for clone failures.'),
        ];
    }

    public static function getFieldDefinitions()
    {
        return [
            'name' => [
                'label'        => __('Title'),
                'form_name'    => 'name',
                'clone_name'   => 'clone_name',
                'ticket_field' => 'name',
                'input_type'   => 'text',
                'config_key'   => 'field_name_mode',
                'allowed_modes' => [
                    self::MODE_READONLY,
                    self::MODE_HIDDEN,
                ],
                'order'        => 10,
            ],
            'type' => [
                'label'        => _n('Type', 'Types', 1),
                'form_name'    => 'type',
                'clone_name'   => 'clone_type',
                'ticket_field' => 'type',
                'input_type'   => 'dropdown_type',
                'config_key'   => 'field_type_mode',
                'allowed_modes' => [
                    self::MODE_EDITABLE,
                    self::MODE_READONLY,
                    self::MODE_HIDDEN,
                ],
                'order'        => 20,
            ],
            'category' => [
                'label'        => _n('Category', 'Categories', 1),
                'form_name'    => 'itilcategories_id',
                'clone_name'   => 'clone_itilcategories_id',
                'ticket_field' => 'itilcategories_id',
                'input_type'   => 'dropdown_category',
                'config_key'   => 'field_category_mode',
                'allowed_modes' => [
                    self::MODE_EDITABLE,
                    self::MODE_READONLY,
                    self::MODE_HIDDEN,
                ],
                'order'        => 30,
            ],
        ];
    }

    public static function getAllowedModesForField(string $field_key): array
    {
        $definitions = self::getFieldDefinitions();
        $allowed = (array) ($definitions[$field_key]['allowed_modes'] ?? [
            self::MODE_EDITABLE,
            self::MODE_READONLY,
            self::MODE_HIDDEN,
        ]);
        $supported_modes = array_keys(self::getModeOptions());
        return array_values(array_filter($allowed, static fn($mode) => in_array($mode, $supported_modes, true)));
    }

    public static function getFieldModes()
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        $definitions = self::getFieldDefinitions();
        $modes = [];
        foreach ($definitions as $key => $def) {
            $default_mode = self::getDefaults()[$def['config_key']] ?? self::MODE_EDITABLE;
            $value = $config[$def['config_key']] ?? $default_mode;
            $allowed_modes = self::getAllowedModesForField((string) $key);
            $modes[$key] = in_array($value, $allowed_modes, true) ? $value : $default_mode;
        }
        return $modes;
    }

    public static function getRemoveAuthorDefault()
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return (int) ($config['remove_author_default'] ?? 1);
    }

    public static function isTimelineLogEnabled(string $key): bool
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return !empty($config[$key]);
    }

    public static function isGlobalCloneActionsBlocked(): bool
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return !empty($config[self::CONFIG_KEY_GLOBAL_BLOCK_CLONE_ACTIONS]);
    }

    public static function isCloneOperationsEnabled(): bool
    {
        $group_toggles = self::getPermissionGroupToggles();
        return !empty($group_toggles['clone']);
    }

    public static function isGlobalActorFieldsBlocked(): bool
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return !empty($config[self::CONFIG_KEY_GLOBAL_BLOCK_ACTOR_FIELDS]);
    }

    public static function getModeOptions()
    {
        return [
            self::MODE_EDITABLE => t_ebenezerclone('Editable'),
            self::MODE_READONLY => t_ebenezerclone('Read-only'),
            self::MODE_HIDDEN => t_ebenezerclone('Hidden'),
        ];
    }

    public static function configUpdate($input)
    {
        $defaults = self::getDefaults();
        $current_values = array_merge($defaults, Config::getConfigurationValues('ebenezerclone'));
        $modes = array_keys(self::getModeOptions());
        $output = [];

        foreach (array_keys($defaults) as $key) {
            if (
                $key === self::CONFIG_KEY_PROFILE_PERMISSION_MATRIX
                || $key === self::CONFIG_KEY_GLOBAL_PERMISSION_POLICIES
                || $key === self::CONFIG_KEY_PERMISSION_GROUP_TOGGLES
                || $key === self::CONFIG_KEY_TICKET_PROPERTY_PROFILE_POLICIES
                || $key === self::CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES
            ) {
                continue;
            }

            if (str_ends_with($key, '_mode')) {
                $value = $input[$key] ?? $defaults[$key];
                $field_key = null;
                foreach (self::getFieldDefinitions() as $candidate_field_key => $definition) {
                    if (($definition['config_key'] ?? '') === $key) {
                        $field_key = (string) $candidate_field_key;
                        break;
                    }
                }
                $allowed_modes = $field_key !== null ? self::getAllowedModesForField($field_key) : $modes;
                $output[$key] = in_array($value, $allowed_modes, true) ? $value : $defaults[$key];
                continue;
            }

            if ($key === 'remove_author_default') {
                if (array_key_exists($key, $input)) {
                    $output[$key] = !empty($input[$key]) ? 1 : 0;
                } else {
                    $output[$key] = (int) ($current_values[$key] ?? $defaults[$key]);
                }
                continue;
            }

            if (array_key_exists($key, self::getTimelineLogDefinitions())) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            if ($key === self::CONFIG_KEY_GLOBAL_BLOCK_CLONE_ACTIONS) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            if ($key === self::CONFIG_KEY_GLOBAL_BLOCK_ACTOR_FIELDS) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            if ($key === self::CONFIG_KEY_GLOBAL_BLOCK_ALL_PROPERTIES) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            if ($key === self::CONFIG_KEY_ALLOW_EMPTY_CATEGORY_EDITION) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            if ($key === self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            $output[$key] = $input[$key] ?? $defaults[$key];
        }

        $scope_data = self::getPermissionScopeData();
        $authorizations = self::normalizeAuthorizations($input['authorized_profiles'] ?? $scope_data['authorizations']);
        $profile_permissions = self::normalizePermissionMatrix($input['profile_permissions'] ?? $scope_data['permissions']);
        $ticket_property_policies = self::normalizeTicketPropertyProfilePolicies(
            $input['ticket_property_profile_policies'] ?? $scope_data['ticket_property_policies']
        );
        $global_copy_policies = self::normalizeGlobalCloneCopyPolicies(
            $input['global_clone_copy_policies'] ?? $scope_data['global_copy_policies']
        );
        $global_policies = self::normalizeGlobalPermissionPolicies($input['global_permissions'] ?? $scope_data['global_policies']);
        $group_toggles = self::normalizePermissionGroupToggles(
            $input['permission_group_toggles'] ?? $scope_data['group_toggles']
        );
        $profile_action = trim((string) ($input['ebenezerclone_profile_action'] ?? ''));

        if ($profile_action === 'add') {
            $new_profiles_id = (int) ($input['new_authorization_profiles_id'] ?? 0);
            $new_entities_id = (int) ($input['new_authorization_entities_id'] ?? -1);
            $new_is_recursive = !empty($input['new_authorization_is_recursive']) ? 1 : 0;

            if ($new_profiles_id > 0 && $new_entities_id >= 0) {
                $new_authorization_id = self::buildAuthorizationId($new_profiles_id, $new_entities_id, $new_is_recursive);
                $authorizations[$new_authorization_id] = [
                    'profiles_id'  => $new_profiles_id,
                    'entities_id'  => $new_entities_id,
                    'is_recursive' => $new_is_recursive,
                ];
                if (!isset($profile_permissions[$new_authorization_id])) {
                    $profile_permissions[$new_authorization_id] = [];
                }
            } else {
                Session::addMessageAfterRedirect(
                    __('One or more required fields are missing'),
                    false,
                    ERROR
                );
            }
        }

        $remove_authorization_id = trim((string) ($input['remove_profile_authorization_target'] ?? ''));
        if ($profile_action === 'remove' && $remove_authorization_id !== '') {
            unset($authorizations[$remove_authorization_id], $profile_permissions[$remove_authorization_id]);
        }

        foreach (array_keys($profile_permissions) as $authorization_id) {
            if (!isset($authorizations[$authorization_id])) {
                unset($profile_permissions[$authorization_id]);
            }
        }
        foreach (array_keys($ticket_property_policies) as $authorization_id) {
            if (!isset($authorizations[$authorization_id])) {
                unset($ticket_property_policies[$authorization_id]);
            }
        }
        // Keep every authorization with an explicit checkbox state per property.
        $ticket_property_definitions = self::getTicketPropertyDefinitions();
        foreach ($authorizations as $authorization_id => $authorization_data) {
            if (!isset($ticket_property_policies[$authorization_id]) || !is_array($ticket_property_policies[$authorization_id])) {
                $ticket_property_policies[$authorization_id] = [];
            }
            foreach ($ticket_property_definitions as $property_key => $property_definition) {
                if (!array_key_exists($property_key, $ticket_property_policies[$authorization_id])) {
                    $ticket_property_policies[$authorization_id][$property_key] = 0;
                }
            }
        }

        $output[self::CONFIG_KEY_PROFILE_PERMISSION_MATRIX] = json_encode(
            [
                'authorizations' => $authorizations,
                'permissions'    => self::normalizePermissionMatrix($profile_permissions),
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
        $output[self::CONFIG_KEY_GLOBAL_PERMISSION_POLICIES] = json_encode(
            self::normalizeGlobalPermissionPolicies($global_policies),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
        $output[self::CONFIG_KEY_PERMISSION_GROUP_TOGGLES] = json_encode(
            self::normalizePermissionGroupToggles($group_toggles),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
        $output[self::CONFIG_KEY_TICKET_PROPERTY_PROFILE_POLICIES] = json_encode(
            self::normalizeTicketPropertyProfilePolicies($ticket_property_policies),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
        $output[self::CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES] = json_encode(
            self::normalizeGlobalCloneCopyPolicies($global_copy_policies),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';

        Session::addMessageAfterRedirect(__('Item successfully updated'), false, INFO);

        return $output;
    }

    public function showFormDisplay()
    {
        if (!Config::canView()) {
            return false;
        }

        $values = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        $canedit = Session::haveRight(Config::$rightname, UPDATE);
        $mode_options = self::getModeOptions();
        $definitions = self::getFieldDefinitions();
        uasort($definitions, fn($a, $b) => $a['order'] <=> $b['order']);
        $permission_definitions = self::getPermissionDefinitions();
        $permission_groups = self::getPermissionGroups();
        $permission_matrix = self::getProfilePermissionMatrix();
        $ticket_property_policies = self::getTicketPropertyProfilePolicies();
        $ticket_property_definitions = self::getTicketPropertyDefinitions();
        $global_copy_policies = self::getGlobalCloneCopyPolicies();
        $clone_copy_definitions = self::getCloneCopyDefinitions();
        $clone_copy_policy_options = self::getCloneCopyPolicyOptions();
        $group_toggles = self::getPermissionGroupToggles();
        $available_profiles = self::getAvailableProfiles();
        $authorizations = self::getProfileAuthorizations();
        $field_labels = [];
        foreach ($definitions as $def) {
            $field_labels[$def['config_key']] = $def['label'];
        }

        if ($canedit) {
            echo "<form name='form' action='" . Toolbox::getItemTypeFormURL('Config') . "' method='post'>";
        }

        echo Html::hidden('config_context', ['value' => 'ebenezerclone']);
        echo Html::hidden('config_class', ['value' => __CLASS__]);
        echo Html::hidden('ebenezerclone_profile_action', ['id' => 'ebz_profile_action', 'value' => 'save']);
        echo Html::hidden('remove_profile_authorization_target', ['id' => 'ebz_remove_auth', 'value' => '']);

        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";
        $clone_form_fields_tooltip = Html::showToolTip(
            t_ebenezerclone('Editable: field can be changed in clone form. Read-only: field is visible but cannot be changed. Hidden: field is not shown in clone form.'),
            ['display' => false]
        );
        echo "<tr><th colspan='2'>" . t_ebenezerclone('Clone form fields') . "&nbsp;$clone_form_fields_tooltip</th></tr>";

        foreach ($field_labels as $key => $label) {
            $value = $values[$key] ?? self::MODE_EDITABLE;
            $field_key = null;
            foreach ($definitions as $definition_key => $definition) {
                if (($definition['config_key'] ?? '') === $key) {
                    $field_key = (string) $definition_key;
                    break;
                }
            }
            $field_mode_options = $mode_options;
            if ($field_key !== null) {
                $allowed_modes = self::getAllowedModesForField($field_key);
                $field_mode_options = array_intersect_key($mode_options, array_flip($allowed_modes));
            }
            echo "<tr class='tab_bg_1'><td class='left'>" . $label . "</td><td class='left'>";
            if ($canedit) {
                echo Dropdown::showFromArray($key, $field_mode_options, [
                    'value' => $value,
                    'display' => false,
                ]);
            } else {
                echo $field_mode_options[$value] ?? ($field_mode_options[self::MODE_READONLY] ?? $mode_options[self::MODE_READONLY]);
            }
            echo "</td></tr>";
        }

        echo "<tr><th colspan='2'>" . t_ebenezerclone('Global clone copy policy') . "</th></tr>";
        echo "<tr class='tab_bg_1'><td colspan='2'>";
        echo "<div id='ebz_global_copy_policy_section' class='border rounded p-3 mb-2'>";

        $clone_copy_sections = self::getCloneCopySections();
        $grouped_copy_definitions = [];
        foreach ($clone_copy_sections as $section_key => $section_definition) {
            $grouped_copy_definitions[$section_key] = [];
        }
        foreach ($clone_copy_definitions as $copy_key => $copy_definition) {
            $section_key = (string) ($copy_definition['section'] ?? 'ticket');
            if (!isset($grouped_copy_definitions[$section_key])) {
                $grouped_copy_definitions[$section_key] = [];
            }
            $grouped_copy_definitions[$section_key][$copy_key] = $copy_definition;
        }

        echo "<div class='mb-3'>";
        echo "<div class='mb-2'><strong>" . t_ebenezerclone('Global rules') . "</strong></div>";
        $force_assigned_status_tooltip = Html::showToolTip(
            t_ebenezerclone('Checked: cloned tickets are always created with status Assigned. Unchecked: status follows the clone copy policy for the Status field.'),
            ['display' => false]
        );
        echo "<div class='mb-2'>";
        if ($canedit) {
            Html::showCheckbox([
                'name'    => self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE,
                'checked' => !empty($values[self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE]),
            ]);
        } else {
            echo !empty($values[self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE]) ? __('Yes') : __('No');
        }
        echo "&nbsp;<span>" . t_ebenezerclone('Force cloned ticket status to Assigned') . "</span>";
        echo "&nbsp;$force_assigned_status_tooltip";
        echo "</div>";
        echo "</div>";

        $clone_fields_tooltip = Html::showToolTip(
            t_ebenezerclone('When checked, the item is cloned. When unchecked, the item is not cloned.'),
            ['display' => false]
        );
        echo "<div class='mb-2'><strong>" . t_ebenezerclone('Clone catalog') . "</strong>&nbsp;$clone_fields_tooltip</div>";
        if ($canedit) {
            echo "<div class='d-flex flex-wrap gap-2 mb-2'>";
            echo "<button type='button' id='ebz_clone_copy_fields_mark_all' class='btn btn-outline-secondary btn-sm'>" . t_ebenezerclone('Mark all') . "</button>";
            echo "<button type='button' id='ebz_clone_copy_fields_unmark_all' class='btn btn-outline-secondary btn-sm'>" . t_ebenezerclone('Unmark all') . "</button>";
            echo "</div>";
        }
        foreach ($clone_copy_sections as $section_key => $section_definition) {
            $section_definitions = $grouped_copy_definitions[$section_key] ?? [];
            if (!count($section_definitions)) {
                continue;
            }
            echo "<div class='mb-3'>";
            echo "<div class='mb-2'><strong>" . $section_definition['label'] . "</strong></div>";
            echo "<div class='row g-2'>";
            foreach ($section_definitions as $copy_key => $copy_definition) {
                $copy_label = (string) ($copy_definition['label'] ?? $copy_key);
                $tooltip = (string) ($copy_definition['tooltip'] ?? '');
                $current_policy = (string) ($global_copy_policies[$copy_key] ?? self::COPY_POLICY_COPY);
                if (!array_key_exists($current_policy, $clone_copy_policy_options)) {
                    $current_policy = self::COPY_POLICY_COPY;
                }
                $checked = ($current_policy === self::COPY_POLICY_COPY);
                echo "<div class='col-xl-4 col-lg-4 col-md-6 col-12 border rounded p-2'>";
                if ($canedit) {
                    echo Html::hidden(
                        sprintf('global_clone_copy_policies[%s]', $copy_key),
                        ['value' => self::COPY_POLICY_IGNORE]
                    );
                    Html::showCheckbox([
                        'name'          => sprintf('global_clone_copy_policies[%s]', $copy_key),
                        'value'         => self::COPY_POLICY_COPY,
                        'checked'       => $checked,
                        'class'         => 'ebz-clone-copy-field-toggle',
                        'zero_on_empty' => false,
                    ]);
                } else {
                    echo $checked ? __('Yes') : __('No');
                }
                echo "&nbsp;<span>" . htmlspecialchars(Toolbox::stripTags($copy_label), ENT_QUOTES, 'UTF-8') . "</span>";
                if ($tooltip !== '') {
                    echo "&nbsp;" . Html::showToolTip($tooltip, ['display' => false]);
                }
                echo "</div>";
            }
            echo "</div>";
            echo "</div>";
        }

        if ($canedit) {
            $js = <<<JAVASCRIPT
(function() {
    var markAll = document.getElementById('ebz_clone_copy_fields_mark_all');
    var unmarkAll = document.getElementById('ebz_clone_copy_fields_unmark_all');
    if (!markAll || !unmarkAll) {
        return;
    }

    var setState = function(checked) {
        var toggles = document.querySelectorAll('.ebz-clone-copy-field-toggle');
        toggles.forEach(function(toggle) {
            toggle.checked = checked;
        });
    };

    markAll.addEventListener('click', function() {
        setState(true);
    });
    unmarkAll.addEventListener('click', function() {
        setState(false);
    });
})();
JAVASCRIPT;
            echo Html::scriptBlock($js);
        }

        echo "</div>";
        echo "</td></tr>";

        echo "<tr><th colspan='2'>" . t_ebenezerclone('Timeline history logs') . "</th></tr>";
        $timeline_tooltips = self::getTimelineLogTooltips();

        foreach (self::getTimelineLogDefinitions() as $key => $label) {
            $timeline_tooltip = Html::showToolTip((string) ($timeline_tooltips[$key] ?? ''), ['display' => false]);
            echo "<tr class='tab_bg_1'><td class='left'>" . $label . "&nbsp;" . $timeline_tooltip . "</td><td class='left'>";
            if ($canedit) {
                Html::showCheckbox([
                    'name' => $key,
                    'checked' => !empty($values[$key]),
                ]);
            } else {
                echo !empty($values[$key]) ? __('Yes') : __('No');
            }
            echo "</td></tr>";
        }

        echo "<tr><th colspan='2'>" . t_ebenezerclone('Global permissions') . "</th></tr>";
        echo "<tr class='tab_bg_1'><td colspan='2'>";
        echo "<div class='border rounded p-3 mb-2'>";
        $global_block_clone_tooltip = Html::showToolTip(
            t_ebenezerclone('Checked: blocks Clone action in ticket actions and Clone action in massive actions for all profiles. If profile matrix is checked for this rule, it can override and allow. Unchecked: plugin ignores profile matrix for this rule and keeps core/other plugins rules.'),
            ['display' => false]
        );
        echo "<div class='mb-2'>";
        if ($canedit) {
            Html::showCheckbox([
                'name'    => self::CONFIG_KEY_GLOBAL_BLOCK_CLONE_ACTIONS,
                'checked' => !empty($values[self::CONFIG_KEY_GLOBAL_BLOCK_CLONE_ACTIONS]),
            ]);
        } else {
            echo !empty($values[self::CONFIG_KEY_GLOBAL_BLOCK_CLONE_ACTIONS]) ? __('Yes') : __('No');
        }
        echo "&nbsp;<span>" . t_ebenezerclone('Global block for clone actions') . "</span>&nbsp;$global_block_clone_tooltip";
        echo "</div>";
        $global_block_actors_tooltip = Html::showToolTip(
            t_ebenezerclone('Checked: plugin blocks requester, observer and assignee actor fields for all profiles by default. Profile matrix can explicitly allow each actor field. Unchecked: plugin ignores actor-field matrix and keeps core/other plugins rules.'),
            ['display' => false]
        );
        echo "<div class='mb-2'>";
        if ($canedit) {
            Html::showCheckbox([
                'name'    => self::CONFIG_KEY_GLOBAL_BLOCK_ACTOR_FIELDS,
                'checked' => !empty($values[self::CONFIG_KEY_GLOBAL_BLOCK_ACTOR_FIELDS]),
            ]);
        } else {
            echo !empty($values[self::CONFIG_KEY_GLOBAL_BLOCK_ACTOR_FIELDS]) ? __('Yes') : __('No');
        }
        echo "&nbsp;<span>" . t_ebenezerclone('Global block for actor fields') . "</span>&nbsp;$global_block_actors_tooltip";
        echo "</div>";
        $global_block_properties_tooltip = Html::showToolTip(
            t_ebenezerclone('Checked: all ticket properties are blocked for editing by default. Profile policy can explicitly allow each field for the logged profile. Unchecked: plugin does not block ticket properties by default. This control affects only form editability and does not change business rules.'),
            ['display' => false]
        );
        echo "<div class='mb-2'>";
        if ($canedit) {
            Html::showCheckbox([
                'name'    => self::CONFIG_KEY_GLOBAL_BLOCK_ALL_PROPERTIES,
                'checked' => !empty($values[self::CONFIG_KEY_GLOBAL_BLOCK_ALL_PROPERTIES]),
            ]);
        } else {
            echo !empty($values[self::CONFIG_KEY_GLOBAL_BLOCK_ALL_PROPERTIES]) ? __('Yes') : __('No');
        }
        echo "&nbsp;<span>" . t_ebenezerclone('Global block for ticket properties') . "</span>&nbsp;$global_block_properties_tooltip";
        echo "</div>";
        $allow_empty_category_edition_tooltip = Html::showToolTip(
            t_ebenezerclone('Checked: when ticket category is empty, plugin does not lock category field and allows editing. Unchecked: category follows the normal profile/global edit policy and core rules.'),
            ['display' => false]
        );
        echo "<div class='mb-2'>";
        if ($canedit) {
            Html::showCheckbox([
                'name'    => self::CONFIG_KEY_ALLOW_EMPTY_CATEGORY_EDITION,
                'checked' => !empty($values[self::CONFIG_KEY_ALLOW_EMPTY_CATEGORY_EDITION]),
            ]);
        } else {
            echo !empty($values[self::CONFIG_KEY_ALLOW_EMPTY_CATEGORY_EDITION]) ? __('Yes') : __('No');
        }
        echo "&nbsp;<span>" . t_ebenezerclone('Allow empty category edition') . "</span>&nbsp;$allow_empty_category_edition_tooltip";
        echo "</div>";
        $global_permission_tooltip = Html::showToolTip(
            t_ebenezerclone('Checked: layer is enabled and profile matrix is respected. Unchecked: plugin does not enforce this layer and keeps core/other plugins rules.'),
            ['display' => false]
        );
        echo "<div class='fw-bold mb-2'>" . t_ebenezerclone('Global permission by layer') . "&nbsp;$global_permission_tooltip</div>";
        echo "<div class='row g-2'>";
        foreach ($permission_groups as $group_key => $group_definition) {
            $group_label = $group_definition['label'] ?? $group_key;
            $group_tooltip = Html::showToolTip((string) ($group_definition['tooltip'] ?? ''), ['display' => false]);
            $group_enabled = !empty($group_toggles[$group_key]);
            echo "<div class='col-md-4'>";
            if ($canedit) {
                Html::showCheckbox([
                    'name'    => sprintf('permission_group_toggles[%s]', $group_key),
                    'checked' => $group_enabled,
                ]);
            } else {
                echo $group_enabled ? __('Yes') : __('No');
            }
            echo "&nbsp;<span>$group_label</span>&nbsp;$group_tooltip";
            echo "</div>";
        }
        echo "</div>";
        echo "</td></tr>";

        echo "<tr><th colspan='2'>" . t_ebenezerclone('Profile permissions matrix') . "</th></tr>";
        echo "<tr class='tab_bg_1'><td colspan='2'>";
        echo "<div class='card mb-3'><div class='card-header'><strong>" . t_ebenezerclone('Add profile authorization') . "</strong></div>";
        echo "<div class='card-body'><div class='row g-3 align-items-end'>";
        $entity_auth_tooltip = Html::showToolTip(
            t_ebenezerclone('Entity defines where this profile authorization applies.'),
            ['display' => false]
        );
        $profile_auth_tooltip = Html::showToolTip(
            t_ebenezerclone('Profile defines which profile receives this authorization in the selected entity scope.'),
            ['display' => false]
        );
        $recursive_auth_tooltip = Html::showToolTip(
            t_ebenezerclone('Recursive set to Yes applies authorization to child entities. Recursive set to No applies only to selected entity.'),
            ['display' => false]
        );
        echo "<div class='col-md-4'><label class='form-label'>" . Entity::getTypeName(1) . "&nbsp;$entity_auth_tooltip</label>";
        if ($canedit) {
            Entity::dropdown([
                'name'   => 'new_authorization_entities_id',
                'entity' => $_SESSION['glpiactiveentities'] ?? [],
                'value'  => (int) ($_POST['new_authorization_entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
            ]);
        } else {
            echo Dropdown::getDropdownName('glpi_entities', (int) ($_SESSION['glpiactive_entity'] ?? 0));
        }
        echo "</div>";
        echo "<div class='col-md-3'><label class='form-label'>" . __('Profile') . "&nbsp;$profile_auth_tooltip</label>";
        if ($canedit) {
            Profile::dropdownUnder([
                'name'  => 'new_authorization_profiles_id',
                'value' => (int) ($_POST['new_authorization_profiles_id'] ?? Profile::getDefault()),
            ]);
        } else {
            echo '-';
        }
        echo "</div>";
        echo "<div class='col-md-2'><label class='form-label'>" . __('Recursive') . "&nbsp;$recursive_auth_tooltip</label>";
        if ($canedit) {
            Dropdown::showYesNo('new_authorization_is_recursive', (int) ($_POST['new_authorization_is_recursive'] ?? 0));
        } else {
            echo __('No');
        }
        echo "</div>";
        echo "<div class='col-md-3 text-end'>";
        if ($canedit) {
            echo Html::submit(_sx('button', 'Add'), [
                'name'    => 'update',
                'class'   => 'btn btn-outline-primary',
                'onclick' => "document.getElementById('ebz_profile_action').value='add';document.getElementById('ebz_remove_auth').value='';",
            ]);
        }
        echo "</div></div></div></div>";

        if (!count($authorizations)) {
            echo "<div class='alert alert-info'>" . t_ebenezerclone('No profile authorization configured.') . "</div>";
        } else {
            $normalized_authorizations = [];
            foreach ($authorizations as $authorization_id => $authorization) {
                $entities_id = (int) ($authorization['entities_id'] ?? 0);
                $profiles_id = (int) ($authorization['profiles_id'] ?? 0);
                $is_recursive = !empty($authorization['is_recursive']);

                $profile_name = $available_profiles[$profiles_id] ?? Dropdown::getDropdownName('glpi_profiles', $profiles_id);
                if (!is_string($profile_name) || trim($profile_name) === '') {
                    $profile_name = '#' . $profiles_id;
                }
                $profile_name = htmlspecialchars(Toolbox::stripTags($profile_name), ENT_QUOTES, 'UTF-8');

                $normalized_authorizations[$authorization_id] = [
                    'entities_id'   => $entities_id,
                    'profile_name'  => $profile_name,
                    'is_recursive'  => $is_recursive,
                ];

                echo Html::hidden(sprintf('authorized_profiles[%1$s][entities_id]', $authorization_id), ['value' => $entities_id]);
                echo Html::hidden(sprintf('authorized_profiles[%1$s][profiles_id]', $authorization_id), ['value' => $profiles_id]);
                echo Html::hidden(sprintf('authorized_profiles[%1$s][is_recursive]', $authorization_id), ['value' => $is_recursive ? 1 : 0]);
            }

            echo "<style id='ebz-authorizations-accordion-styles'>"
                . "#ebz_authorizations_accordion{display:flex;flex-direction:column;gap:0.75rem;}"
                . "#ebz_authorizations_accordion .ebz-auth-item{border:1px solid #d9dee8;border-radius:0.75rem;overflow:hidden;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,0.04);}"
                . "#ebz_authorizations_accordion .ebz-auth-button{padding:1rem 1.25rem;box-shadow:none;border:0;background:#fff;}"
                . "#ebz_authorizations_accordion .ebz-auth-button:not(.collapsed){background:#f8fafc;color:inherit;box-shadow:none;}"
                . "#ebz_authorizations_accordion .ebz-auth-button:focus{box-shadow:none;}"
                . "#ebz_authorizations_accordion .ebz-auth-summary{display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem 1rem;width:calc(100% - 1.5rem);padding-right:0.5rem;}"
                . "#ebz_authorizations_accordion .ebz-auth-profile{margin:0;}"
                . "#ebz_authorizations_accordion .accordion-body{border-top:1px solid #e5e7eb;}"
                . "</style>";
            echo "<div class='accordion' id='ebz_authorizations_accordion'>";
            $index = 0;
            foreach ($normalized_authorizations as $authorization_id => $authorization_data) {
                $index++;
                $collapse_id = 'ebz_auth_collapse_' . $index;
                $heading_id = 'ebz_auth_heading_' . $index;
                $entity_label = Dropdown::getDropdownName('glpi_entities', (int) $authorization_data['entities_id']);
                $recursive_label = $authorization_data['is_recursive'] ? __('Recursive') : __('No');
                echo "<div class='accordion-item ebz-auth-item'>";
                echo "<h2 class='accordion-header' id='$heading_id'>";
                echo "<button class='accordion-button collapsed ebz-auth-button' type='button' data-bs-toggle='collapse' data-bs-target='#$collapse_id' aria-expanded='false' aria-controls='$collapse_id'>";
                echo "<span class='ebz-auth-summary'>"
                    . "<strong class='ebz-auth-profile'>" . $authorization_data['profile_name'] . "</strong>"
                    . "<span class='text-muted'>$entity_label</span>"
                    . "<span class='badge bg-secondary'>$recursive_label</span>"
                    . "</span>";
                echo "</button></h2>";
                echo "<div id='$collapse_id' class='accordion-collapse collapse' aria-labelledby='$heading_id' data-bs-parent='#ebz_authorizations_accordion'>";
                echo "<div class='accordion-body'>";
                if ($canedit) {
                    $remove_onclick = "document.getElementById('ebz_profile_action').value='remove';"
                        . "document.getElementById('ebz_remove_auth').value='" . addslashes($authorization_id) . "';";
                    $remove_profile_tooltip = Html::showToolTip(
                        t_ebenezerclone('Permanently removes this profile authorization from the permissions matrix.'),
                        ['display' => false]
                    );
                    echo "<div class='d-flex justify-content-start align-items-center gap-2 mb-2'>";
                    echo Html::submit(t_ebenezerclone('Remove profile'), [
                        'name'    => 'update',
                        'class'   => 'btn btn-outline-danger btn-sm',
                        'onclick' => $remove_onclick,
                    ]);
                    echo $remove_profile_tooltip;
                    echo "</div>";
                }
                foreach ($permission_groups as $group_key => $group_definition) {
                    $group_label = $group_definition['label'] ?? '';
                    $group_permission_keys = array_values(array_filter(
                        (array) ($group_definition['permissions'] ?? []),
                        static fn($permission_key) => isset($permission_definitions[$permission_key])
                    ));
                    if (!count($group_permission_keys)) {
                        continue;
                    }

                    echo "<div class='border rounded p-3 mb-2'>";
                    echo "<div class='fw-bold mb-2'>$group_label</div>";
                    echo "<div class='row g-2'>";
                    $permission_col_class = in_array((string) $group_key, ['clone', 'assignment'], true)
                        ? 'col-xl-4 col-lg-4 col-md-6 col-12'
                        : 'col-md-6';
                    foreach ($group_permission_keys as $permission_key) {
                        $permission_definition = $permission_definitions[$permission_key];
                        $tooltip = Html::showToolTip($permission_definition['tooltip'], ['display' => false]);
                        $policy = !empty($permission_matrix[$authorization_id][$permission_key]);
                        echo "<div class='$permission_col_class border rounded p-2'>";
                        if ($canedit) {
                            Html::showCheckbox([
                                'name'    => sprintf('profile_permissions[%1$s][%2$s]', $authorization_id, $permission_key),
                                'checked' => $policy,
                            ]);
                        } else {
                            Html::showCheckbox([
                                'checked'  => $policy,
                                'disabled' => true,
                            ]);
                        }
                        echo "&nbsp;<span>" . $permission_definition['label'] . "</span>&nbsp;$tooltip";
                        echo "</div>";
                    }
                    echo "</div></div>";
                }
                if (count($ticket_property_definitions)) {
                    echo "<div class='border rounded p-3 mb-2'>";
                    $ticket_properties_policy_tooltip = Html::showToolTip(
                        t_ebenezerclone('Checked: the field is enabled for editing for this profile. Unchecked: the field follows the global block rule. This policy only controls form editability and does not affect business rules.'),
                        ['display' => false]
                    );
                    echo "<div class='fw-bold mb-2'>" . t_ebenezerclone('Ticket properties policy') . "&nbsp;$ticket_properties_policy_tooltip</div>";
                    echo "<div class='row g-1'>";
                    foreach ($ticket_property_definitions as $property_key => $property_definition) {
                        $property_label = (string) ($property_definition['label'] ?? $property_key);
                        $is_blocked = !empty($ticket_property_policies[$authorization_id][$property_key]);
                        $is_allowed = !$is_blocked;
                        echo "<div class='col-xl-4 col-lg-4 col-md-6 col-12 border rounded p-2'>";
                        if ($canedit) {
                            echo Html::hidden(
                                sprintf('ticket_property_profile_policies[%1$s][%2$s]', $authorization_id, $property_key),
                                ['value' => 1]
                            );
                            Html::showCheckbox([
                                'name'    => sprintf('ticket_property_profile_policies[%1$s][%2$s]', $authorization_id, $property_key),
                                'value'   => 0,
                                'checked' => $is_allowed,
                                'zero_on_empty' => false,
                            ]);
                            echo "&nbsp;<span style='color: inherit;'>" . htmlspecialchars(Toolbox::stripTags($property_label), ENT_QUOTES, 'UTF-8') . "</span>";
                        } else {
                            echo htmlspecialchars(
                                Toolbox::stripTags($property_label),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        }
                        echo "</div>";
                    }
                    echo "</div></div>";
                }
                echo "</div></div></div>";
            }
            echo "</div>";
        }
        echo "</td></tr>";

        if ($canedit) {
            echo "<tr class='tab_bg_1'><td colspan='2' class='center'>";
            echo Html::submit(_sx('button', 'Save'), [
                'name'    => 'update',
                'class'   => 'btn btn-primary',
                'onclick' => "document.getElementById('ebz_profile_action').value='save';document.getElementById('ebz_remove_auth').value='';",
            ]);
            echo "</td></tr>";
        }

        echo "</table></div>";

        if ($canedit) {
            Html::closeForm();
        }

        return true;
    }

    public static function install()
    {
        $defaults = self::getDefaults();
        $current = Config::getConfigurationValues('ebenezerclone');
        if (empty($current)) {
            $legacy = Config::getConfigurationValues('tr' . 'tclone');
            if (!empty($legacy)) {
                $current = $legacy;
            }
        }
        Config::setConfigurationValues('ebenezerclone', array_merge($defaults, $current));
    }

    public static function uninstall()
    {
        Config::deleteConfigurationValues('ebenezerclone');
    }
}
