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
    public const CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES = 'global_clone_copy_policies';
    public const CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE = 'force_assigned_status_on_clone';
    public const CONFIG_KEY_RECALCULATE_TITLE_FROM_CATEGORY = 'recalculate_title_from_category';
    public const CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS = 'show_hidden_related_tickets';
    public const CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION = 'require_glpi_ticket_create_permission';

    public const PERMISSION_CLONE_TICKET = 'clone_ticket';
    public const COPY_POLICY_COPY = 'copy';
    public const COPY_POLICY_IGNORE = 'ignore';

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
            'remove_author_default' => 1,
            'timeline_log_clone_created' => 1,
            'timeline_log_clone_source' => 1,
            'timeline_log_ticket_link' => 1,
            'timeline_log_followups' => 0,
            'timeline_log_items_copied' => 0,
            'timeline_log_actors_copied' => 0,
            'timeline_log_clone_failure' => 1,
            self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE => 1,
            self::CONFIG_KEY_RECALCULATE_TITLE_FROM_CATEGORY => 0,
            self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS => 0,
            self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION => 1,
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
        ];
    }

    public static function getSupportedPermissionKeys(): array
    {
        return array_keys(self::getPermissionDefinitions());
    }

    public static function getAvailableProfiles(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $profiles = [];
        $iterator = $DB->request([
            'FROM'  => 'glpi_profiles',
            'ORDER' => 'name',
        ]);

        foreach ($iterator as $row) {
            $profile_id = (int) ($row['id'] ?? 0);
            if ($profile_id <= 0) {
                continue;
            }

            $profiles[$profile_id] = (string) ($row['name'] ?? ('#' . $profile_id));
        }

        if (class_exists('Collator')) {
            $locale = (string) ($_SESSION['glpilanguage'] ?? 'en_US');
            $collator = new Collator($locale);
            uasort($profiles, static function (string $a, string $b) use ($collator): int {
                return $collator->compare($a, $b);
            });
        } else {
            natcasesort($profiles);
        }

        return $profiles;
    }

    public static function getAvailableEntities(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $root_entity_label = t_ebenezerclone('Root entity');
        $root_iterator = $DB->request([
            'FROM'  => 'glpi_entities',
            'WHERE' => [
                'id' => 0,
            ],
            'LIMIT' => 1,
        ]);
        foreach ($root_iterator as $root_row) {
            $root_label = trim((string) ($root_row['completename'] ?? ''));
            if ($root_label === '') {
                $root_label = trim((string) ($root_row['name'] ?? ''));
            }
            if ($root_label !== '') {
                $root_entity_label = $root_label;
            }
            break;
        }

        $entities = [
            0 => $root_entity_label,
        ];

        $iterator = $DB->request([
            'FROM'  => 'glpi_entities',
            'ORDER' => 'completename',
        ]);

        foreach ($iterator as $row) {
            $entity_id = (int) ($row['id'] ?? 0);
            if ($entity_id <= 0) {
                continue;
            }

            $label = trim((string) ($row['completename'] ?? ''));
            if ($label === '') {
                $label = trim((string) ($row['name'] ?? ''));
            }
            if ($label === '') {
                $label = '#' . $entity_id;
            }
            $entities[$entity_id] = $label;
        }

        return $entities;
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

    public static function getCloneCopyDefinitions(): array
    {
        $field_definitions = [];
        $field_tooltips = self::getCloneCopyFieldTooltips();
        foreach (self::getCloneCopyTicketFields() as $field_key => $field_label) {
            $field_definitions[$field_key] = [
                'label'   => $field_label,
                'kind'    => 'field',
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
                    'tooltip' => t_ebenezerclone('Checked: copies requester actors to the cloned ticket. Unchecked: does not copy requester actors.'),
                ],
                'actor_observer' => [
                    'label'   => t_ebenezerclone('Observer'),
                    'kind'    => 'field',
                    'tooltip' => t_ebenezerclone('Checked: copies observer actors to the cloned ticket. Unchecked: does not copy observer actors.'),
                ],
                'actor_assign' => [
                    'label'   => t_ebenezerclone('Assignee'),
                    'kind'    => 'field',
                    'tooltip' => t_ebenezerclone('Checked: copies assigned actors to the cloned ticket. Unchecked: does not copy assigned actors.'),
                ],
                'items'           => [
                    'label' => t_ebenezerclone('Copy related tickets'),
                    'kind'  => 'component',
                    'tooltip' => t_ebenezerclone('Checked: copies related ticket links from source ticket. Unchecked: does not copy related ticket links.'),
                ],
                'related_items'   => [
                    'label' => t_ebenezerclone('Copy related items (devices)'),
                    'kind'  => 'component',
                    'tooltip' => t_ebenezerclone('Checked: copies items from the Items tab to the cloned ticket. Unchecked: does not copy these items.'),
                ],
                'documents'       => [
                    'label' => t_ebenezerclone('Copy document/attachment links to cloned ticket'),
                    'kind'  => 'component',
                    'tooltip' => t_ebenezerclone('Checked: copies document/attachment links to cloned ticket. Unchecked: does not copy these links.'),
                ],
                'ticket_link'     => [
                    'label' => t_ebenezerclone('Link source and cloned tickets'),
                    'kind'  => 'component',
                    'tooltip' => t_ebenezerclone('Checked: creates link between source and cloned tickets. Unchecked: does not create link between them.'),
                ],
                'followups'       => [
                    'label' => t_ebenezerclone('Create informational activities during cloning'),
                    'kind'  => 'component',
                    'tooltip' => t_ebenezerclone('Checked: creates informational activities during cloning. Unchecked: does not create informational activities.'),
                ],
            ]
        );
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
        $search_labels_map = self::getCloneCopySearchOptionLabelsMap();
        $allowed_fields = [
            'requesttypes_id',
            'urgency',
            'impact',
            'priority',
            'locations_id',
            'users_id_recipient',
        ];
        $fields = [];
        foreach ($allowed_fields as $field) {
            $fields[$field] = $search_labels_map[$field] ?? self::formatFieldKeyForDisplay($field);
        }

        return $fields;
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

    public static function shouldForceAssignedStatusOnClone(): bool
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return !empty($config[self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE]);
    }

    public static function shouldRecalculateTitleFromCategory(): bool
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        return !empty($config[self::CONFIG_KEY_RECALCULATE_TITLE_FROM_CATEGORY]);
    }

    public static function shouldShowHiddenRelatedTickets(): bool
    {
        $config = Config::getConfigurationValues('ebenezerclone');
        if (!array_key_exists(self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS, $config)) {
            return true;
        }

        return !empty($config[self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS]);
    }

    public static function shouldRequireGlpiTicketCreatePermission(): bool
    {
        $config = Config::getConfigurationValues('ebenezerclone');
        if (!array_key_exists(self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION, $config)) {
            return false;
        }

        return !empty($config[self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION]);
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
            if ($authorization_id === '') {
                continue;
            }
            if (!isset($matrix[$authorization_id])) {
                continue;
            }

            if (!empty($matrix[$authorization_id][$permission_key])) {
                return true;
            }
        }

        return false;
    }

    private static function decodePermissionMatrix(string $raw): array
    {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function decodeProfilePermissionScopePayload($scope_payload): ?array
    {
        if (is_array($scope_payload)) {
            return $scope_payload;
        }

        if (!is_scalar($scope_payload)) {
            return null;
        }

        $raw_scope_matrix = trim((string) $scope_payload);
        if ($raw_scope_matrix === '') {
            return null;
        }

        $candidates = [$raw_scope_matrix];
        $html_decoded = html_entity_decode($raw_scope_matrix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($html_decoded !== $raw_scope_matrix) {
            $candidates[] = $html_decoded;
        }

        $url_decoded = urldecode($raw_scope_matrix);
        if ($url_decoded !== $raw_scope_matrix) {
            $candidates[] = $url_decoded;
        }

        $rawurl_decoded = rawurldecode($raw_scope_matrix);
        if ($rawurl_decoded !== $raw_scope_matrix && $rawurl_decoded !== $url_decoded) {
            $candidates[] = $rawurl_decoded;
        }

        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            $decoded_json_string = json_decode($candidate, false);
            if (is_string($decoded_json_string)) {
                $decoded = json_decode($decoded_json_string, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            $base64_candidate = str_replace(' ', '+', $candidate);
            $base64_decoded = base64_decode($base64_candidate, true);
            if (is_string($base64_decoded) && $base64_decoded !== '') {
                $decoded = json_decode($base64_decoded, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    private static function getPermissionScopeData(): array
    {
        $config = array_merge(self::getDefaults(), Config::getConfigurationValues('ebenezerclone'));
        $scope_raw = self::decodePermissionMatrix((string) ($config[self::CONFIG_KEY_PROFILE_PERMISSION_MATRIX] ?? '{}'));
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
                'global_copy_policies' => self::normalizeGlobalCloneCopyPolicies($raw['global_copy_policies'] ?? []),
            ];
        }

        // Backward compatibility: old format was [profiles_id => [permission => 0/1]]
        $legacy_raw = $raw;
        unset($legacy_raw['global_copy_policies']);
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
                if (
                    $permission_key === self::PERMISSION_CLONE_TICKET
                    && !array_key_exists(self::PERMISSION_CLONE_TICKET, $permissions)
                ) {
                    $raw_permission = (
                        !empty($permissions['ticket_clone_action'])
                        || !empty($permissions['massive_clone'])
                    ) ? 1 : 0;
                }
                if (is_array($raw_permission)) {
                    if (array_key_exists('allow', $raw_permission)) {
                        $value = !empty($raw_permission['allow']) ? 1 : 0;
                    } else {
                        $value = !empty($raw_permission) ? 1 : 0;
                    }
                } else {
                    $value = array_key_exists($permission_key, $permissions)
                        ? (!empty($raw_permission) ? 1 : 0)
                        : 0;
                }

                $normalized[$authorization_key][$permission_key] = $value;
            }
        }

        return $normalized;
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

    private static function buildAuthorizationId(int $profiles_id, int $entities_id, int $is_recursive): string
    {
        return sprintf('p%1$d_e%2$d_r%3$d', $profiles_id, $entities_id, $is_recursive ? 1 : 0);
    }

    private static function normalizeAuthorizationRowsFromInput($rows): array
    {
        if (!is_array($rows)) {
            return [
                'authorizations' => [],
                'permissions' => [],
                'invalid_rows' => 0,
            ];
        }

        $authorizations = [];
        $permissions = [];
        $invalid_rows = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $invalid_rows++;
                continue;
            }

            $profiles_id = (int) ($row['profiles_id'] ?? 0);
            $entities_id = (int) ($row['entities_id'] ?? -1);
            $is_recursive = !empty($row['is_recursive']) ? 1 : 0;

            if ($profiles_id <= 0 || $entities_id < 0) {
                $invalid_rows++;
                continue;
            }

            $authorization_id = self::buildAuthorizationId($profiles_id, $entities_id, $is_recursive);
            $authorizations[$authorization_id] = [
                'profiles_id' => $profiles_id,
                'entities_id' => $entities_id,
                'is_recursive' => $is_recursive,
            ];
            $permissions[$authorization_id] = [
                self::PERMISSION_CLONE_TICKET => 1,
            ];
        }

        return [
            'authorizations' => $authorizations,
            'permissions' => $permissions,
            'invalid_rows' => $invalid_rows,
        ];
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
            'timeline_log_items_copied' => t_ebenezerclone('Log copied links and related items'),
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
            'timeline_log_items_copied' => t_ebenezerclone('Checked: logs copied related ticket links, related items, and copied document links. Unchecked: does not create timeline log for copied links/items/documents.'),
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
            if ($key === self::CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES) {
                continue;
            }

            if (substr($key, -5) === '_mode') {
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

            if ($key === self::CONFIG_KEY_FORCE_ASSIGNED_STATUS_ON_CLONE) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            if ($key === self::CONFIG_KEY_RECALCULATE_TITLE_FROM_CATEGORY) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            if (
                $key === self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS
                || $key === self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION
            ) {
                $output[$key] = !empty($input[$key]) ? 1 : 0;
                continue;
            }

            $output[$key] = $input[$key] ?? $defaults[$key];
        }

        $global_copy_policies = self::normalizeGlobalCloneCopyPolicies(
            $input['global_clone_copy_policies'] ?? self::getGlobalCloneCopyPolicies()
        );
        $output[self::CONFIG_KEY_GLOBAL_CLONE_COPY_POLICIES] = json_encode(
            self::normalizeGlobalCloneCopyPolicies($global_copy_policies),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';

        $scope_to_persist = null;

        if (array_key_exists('ebz_authorizations', $input)) {
            if (!is_array($input['ebz_authorizations'])) {
                Toolbox::logDebug('EBENEZERCLONE invalid authorization payload type; persisting empty scope', [
                    'payload_type' => gettype($input['ebz_authorizations']),
                ]);
            }
            $normalized_rows = self::normalizeAuthorizationRowsFromInput($input['ebz_authorizations']);
            if (($normalized_rows['invalid_rows'] ?? 0) > 0) {
                Toolbox::logDebug('EBENEZERCLONE invalid authorization rows ignored during save', [
                    'invalid_rows' => (int) $normalized_rows['invalid_rows'],
                    'payload_type' => gettype($input['ebz_authorizations']),
                ]);
            }
            $scope_to_persist = [
                'authorizations' => $normalized_rows['authorizations'] ?? [],
                'permissions' => $normalized_rows['permissions'] ?? [],
            ];
        } else {
            // Legacy fallback kept temporarily for backward compatibility.
            $scope_payload = $input[self::CONFIG_KEY_PROFILE_PERMISSION_MATRIX] ?? '';
            $decoded_scope = self::decodeProfilePermissionScopePayload($scope_payload);
            if (is_array($decoded_scope)) {
                $normalized_scope = self::normalizePermissionScopeData($decoded_scope);
                $scope_to_persist = [
                    'authorizations' => $normalized_scope['authorizations'] ?? [],
                    'permissions' => $normalized_scope['permissions'] ?? [],
                ];
            } else {
                Toolbox::logDebug('EBENEZERCLONE invalid legacy authorization payload; persisting empty scope', [
                    'payload_type' => gettype($scope_payload),
                    'payload_length' => is_scalar($scope_payload) ? strlen((string) $scope_payload) : -1,
                ]);
                $scope_to_persist = [
                    'authorizations' => [],
                    'permissions' => [],
                ];
            }
        }

        $output[self::CONFIG_KEY_PROFILE_PERMISSION_MATRIX] = json_encode(
            [
                'authorizations' => $scope_to_persist['authorizations'] ?? [],
                'permissions' => $scope_to_persist['permissions'] ?? [],
            ],
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
        $global_copy_policies = self::getGlobalCloneCopyPolicies();
        $clone_copy_definitions = self::getCloneCopyDefinitions();
        $clone_copy_policy_options = self::getCloneCopyPolicyOptions();
        $available_profiles = self::getAvailableProfiles();
        $available_entities = self::getAvailableEntities();
        $profile_authorizations = self::getProfileAuthorizations();
        $profile_permission_matrix = self::getProfilePermissionMatrix();
        $clone_authorization_rows = [];
        foreach ($profile_authorizations as $authorization_id => $authorization) {
            $auth_id = (string) $authorization_id;
            if ($auth_id === '') {
                continue;
            }
            $has_clone_permission = !empty($profile_permission_matrix[$auth_id][self::PERMISSION_CLONE_TICKET]);
            if (!$has_clone_permission) {
                continue;
            }
            $clone_authorization_rows[] = [
                'profiles_id'  => (int) ($authorization['profiles_id'] ?? 0),
                'entities_id'  => (int) ($authorization['entities_id'] ?? 0),
                'is_recursive' => !empty($authorization['is_recursive']) ? 1 : 0,
            ];
        }
        if (count($clone_authorization_rows) > 1) {
            $locale = (string) ($_SESSION['glpilanguage'] ?? 'en_US');
            $collator = class_exists('Collator') ? new Collator($locale) : null;
            usort($clone_authorization_rows, static function (array $a, array $b) use ($available_profiles, $collator): int {
                $label_a = (string) ($available_profiles[(int) ($a['profiles_id'] ?? 0)] ?? '');
                $label_b = (string) ($available_profiles[(int) ($b['profiles_id'] ?? 0)] ?? '');
                if ($collator instanceof Collator) {
                    return $collator->compare($label_a, $label_b);
                }

                return strcasecmp($label_a, $label_b);
            });
        }
        $field_labels = [];
        foreach ($definitions as $def) {
            $field_labels[$def['config_key']] = $def['label'];
        }

        if ($canedit) {
            echo "<form name='form' action='" . Toolbox::getItemTypeFormURL('Config') . "' method='post'>";
        }

        echo Html::hidden('config_context', ['value' => 'ebenezerclone']);
        echo Html::hidden('config_class', ['value' => __CLASS__]);
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

        echo "<tr><th colspan='2'>" . t_ebenezerclone('Clone authorization by profile and entity') . "</th></tr>";
        echo "<tr class='tab_bg_1'><td colspan='2'>";
        echo "<div class='mb-2'>";
        echo t_ebenezerclone('Only configured profile/entity combinations can see and execute ticket cloning. Recursivity applies to child entities.');
        echo "</div>";

        if ($canedit) {
            echo "<div class='row g-2 align-items-end mb-3' id='ebz_auth_add_section'>";
            echo "<div class='col-md-4'>";
            echo "<label class='form-label'>" . t_ebenezerclone('Entity') . "</label>";
            echo "<select class='form-select' id='ebz_auth_new_entity'>";
            foreach ($available_entities as $entity_id => $entity_name) {
                echo "<option value='" . (int) $entity_id . "'>"
                    . htmlspecialchars((string) $entity_name, ENT_QUOTES, 'UTF-8')
                    . "</option>";
            }
            echo "</select>";
            echo "</div>";

            echo "<div class='col-md-4'>";
            echo "<label class='form-label'>" . t_ebenezerclone('Profile') . "</label>";
            echo "<select class='form-select' id='ebz_auth_new_profile'>";
            echo "<option value='0'>-----</option>";
            foreach ($available_profiles as $profile_id => $profile_name) {
                echo "<option value='" . (int) $profile_id . "'>"
                    . htmlspecialchars((string) $profile_name, ENT_QUOTES, 'UTF-8')
                    . "</option>";
            }
            echo "</select>";
            echo "</div>";

            echo "<div class='col-md-2'>";
            echo "<label class='form-label'>" . t_ebenezerclone('Recursive') . "</label>";
            echo "<select class='form-select' id='ebz_auth_new_recursive'>";
            echo "<option value='0'>" . __('No') . "</option>";
            echo "<option value='1'>" . __('Yes') . "</option>";
            echo "</select>";
            echo "</div>";

            echo "<div class='col-md-2'>";
            echo "<button type='button' class='btn btn-primary w-100' id='ebz_auth_add_row'>"
                . t_ebenezerclone('Add authorization')
                . "</button>";
            echo "</div>";
            echo "</div>";
        }

        echo "<table class='tab_cadre_fixehov' id='ebz_clone_authorizations_table'>";
        echo "<thead><tr>";
        echo "<th>" . t_ebenezerclone('Entity') . "</th>";
        echo "<th>" . t_ebenezerclone('Profile') . "</th>";
        echo "<th>" . t_ebenezerclone('Recursive') . "</th>";
        if ($canedit) {
            echo "<th>" . __('Actions') . "</th>";
        }
        echo "</tr></thead><tbody>";

        foreach ($clone_authorization_rows as $row) {
            $profiles_id = (int) ($row['profiles_id'] ?? 0);
            $entities_id = (int) ($row['entities_id'] ?? 0);
            $is_recursive = !empty($row['is_recursive']) ? 1 : 0;
            $row_key = self::buildAuthorizationId($profiles_id, $entities_id, $is_recursive);

            $profile_label = (string) ($available_profiles[$profiles_id] ?? ('#' . $profiles_id));
            $entity_label = (string) ($available_entities[$entities_id] ?? ('#' . $entities_id));
            $recursive_label = $is_recursive === 1 ? __('Yes') : __('No');

            echo "<tr class='ebz-auth-row' data-profile-id='" . $profiles_id
                . "' data-entity-id='" . $entities_id
                . "' data-recursive='" . $is_recursive . "'>";
            echo "<td class='ebz-auth-entity-label'>" . htmlspecialchars($entity_label, ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td class='ebz-auth-profile-label'>" . htmlspecialchars($profile_label, ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td class='ebz-auth-recursive-label'>" . htmlspecialchars($recursive_label, ENT_QUOTES, 'UTF-8') . "</td>";
            if ($canedit) {
                echo "<td><button type='button' class='btn btn-outline-danger btn-sm ebz-auth-remove'>"
                    . t_ebenezerclone('Remove')
                    . "</button></td>";
                echo Html::hidden(sprintf('ebz_authorizations[%s][profiles_id]', $row_key), ['value' => $profiles_id]);
                echo Html::hidden(sprintf('ebz_authorizations[%s][entities_id]', $row_key), ['value' => $entities_id]);
                echo Html::hidden(sprintf('ebz_authorizations[%s][is_recursive]', $row_key), ['value' => $is_recursive]);
            }
            echo "</tr>";
        }

        echo "</tbody></table>";
        echo "</td></tr>";

        echo "<tr><th colspan='2'>" . t_ebenezerclone('Global clone copy policy') . "</th></tr>";
        echo "<tr class='tab_bg_1'><td colspan='2'>";
        echo "<div id='ebz_global_copy_policy_section' class='border rounded p-3 mb-2'>";

        $component_definitions = [];
        $field_copy_definitions = [];
        foreach ($clone_copy_definitions as $copy_key => $copy_definition) {
            if (($copy_definition['kind'] ?? '') === 'field') {
                $field_copy_definitions[$copy_key] = $copy_definition;
            } else {
                $component_definitions[$copy_key] = $copy_definition;
            }
        }

        if (count($component_definitions) > 0) {
            echo "<div class='mb-3'>";
            echo "<div class='mb-2'><strong>" . t_ebenezerclone('Global rules') . "</strong></div>";
            $force_assigned_status_tooltip = Html::showToolTip(
                t_ebenezerclone('Checked: cloned tickets are always created with status Assigned. Unchecked: status follows the clone copy policy for the Status field.'),
                ['display' => false]
            );
            $recalculate_title_tooltip = Html::showToolTip(
                t_ebenezerclone('Checked: title is always recalculated from selected category in clone form. Unchecked: title always keeps source ticket title.'),
                ['display' => false]
            );
            $show_hidden_related_tickets_tooltip = Html::showToolTip(
                t_ebenezerclone('Checked: shows related tickets hidden by the native interface. This can broaden visibility of relationship metadata and must be enabled only after confidentiality review.'),
                ['display' => false]
            );
            $require_glpi_ticket_create_permission_tooltip = Html::showToolTip(
                t_ebenezerclone('Checked: cloning also requires the native GLPI Ticket create right in the final entity. Unchecked: the plugin authorization still applies, but disabling this requirement increases security risk.'),
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
            echo "<div class='mb-2'>";
            if ($canedit) {
                Html::showCheckbox([
                    'name'    => self::CONFIG_KEY_RECALCULATE_TITLE_FROM_CATEGORY,
                    'checked' => !empty($values[self::CONFIG_KEY_RECALCULATE_TITLE_FROM_CATEGORY]),
                ]);
            } else {
                echo !empty($values[self::CONFIG_KEY_RECALCULATE_TITLE_FROM_CATEGORY]) ? __('Yes') : __('No');
            }
            echo "&nbsp;<span>" . t_ebenezerclone('Recalculate title from selected category') . "</span>";
            echo "&nbsp;$recalculate_title_tooltip";
            echo "</div>";
            echo "<div class='mb-2'>";
            if ($canedit) {
                Html::showCheckbox([
                    'name'    => self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS,
                    'checked' => !empty($values[self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS]),
                ]);
            } else {
                echo !empty($values[self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS]) ? __('Yes') : __('No');
            }
            echo "&nbsp;<span>" . t_ebenezerclone('Show related tickets hidden by native visibility') . "</span>";
            echo "&nbsp;$show_hidden_related_tickets_tooltip";
            echo "</div>";
            echo "<div class='mb-2'>";
            if ($canedit) {
                Html::showCheckbox([
                    'name'    => self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION,
                    'checked' => !empty($values[self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION]),
                ]);
            } else {
                echo !empty($values[self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION]) ? __('Yes') : __('No');
            }
            echo "&nbsp;<span>" . t_ebenezerclone('Require native GLPI Ticket create permission') . "</span>";
            echo "&nbsp;$require_glpi_ticket_create_permission_tooltip";
            echo "</div>";
            foreach ($component_definitions as $copy_key => $copy_definition) {
                $copy_label = (string) ($copy_definition['label'] ?? $copy_key);
                $tooltip = (string) ($copy_definition['tooltip'] ?? t_ebenezerclone('Checked: applies this rule. Unchecked: ignores this rule.'));
                $is_inverted = !empty($copy_definition['inverted']);
                $current_policy = (string) ($global_copy_policies[$copy_key] ?? self::COPY_POLICY_COPY);
                if (!array_key_exists($current_policy, $clone_copy_policy_options)) {
                    $current_policy = self::COPY_POLICY_COPY;
                }
                $checked = $is_inverted
                    ? ($current_policy === self::COPY_POLICY_IGNORE)
                    : ($current_policy === self::COPY_POLICY_COPY);

                echo "<div class='mb-2'>";
                if ($canedit) {
                    echo Html::hidden(
                        sprintf('global_clone_copy_policies[%s]', $copy_key),
                        ['value' => $is_inverted ? self::COPY_POLICY_COPY : self::COPY_POLICY_IGNORE]
                    );
                    Html::showCheckbox([
                        'name'          => sprintf('global_clone_copy_policies[%s]', $copy_key),
                        'value'         => $is_inverted ? self::COPY_POLICY_IGNORE : self::COPY_POLICY_COPY,
                        'checked'       => $checked,
                        'zero_on_empty' => false,
                    ]);
                } else {
                    echo $checked ? __('Yes') : __('No');
                }
                echo "&nbsp;<span>" . htmlspecialchars(Toolbox::stripTags($copy_label), ENT_QUOTES, 'UTF-8') . "</span>";
                echo "&nbsp;" . Html::showToolTip($tooltip, ['display' => false]);
                echo "</div>";
            }
            echo "</div>";
        }

        $clone_fields_tooltip = Html::showToolTip(
            t_ebenezerclone('When checked, the field is cloned. When unchecked, the field is not cloned.'),
            ['display' => false]
        );
        echo "<div class='mb-2'><strong>" . t_ebenezerclone('Ticket fields for cloning') . "</strong>&nbsp;$clone_fields_tooltip</div>";
        echo "<div class='alert alert-info mb-3'>"
            . t_ebenezerclone('Title, Type, Category and Content are always copied from the cloning form and are not part of the configurable list.')
            . "</div>";
        if ($canedit) {
            echo "<div class='d-flex flex-wrap gap-2 mb-2'>";
            echo "<button type='button' id='ebz_clone_copy_fields_mark_all' class='btn btn-outline-secondary btn-sm'>" . t_ebenezerclone('Check all') . "</button>";
            echo "<button type='button' id='ebz_clone_copy_fields_unmark_all' class='btn btn-outline-secondary btn-sm'>" . t_ebenezerclone('Uncheck all') . "</button>";
            echo "</div>";
        }
        echo "<div class='row g-2'>";
        foreach ($field_copy_definitions as $copy_key => $copy_definition) {
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

            $profiles_json = json_encode(
                array_map(
                    static fn($id, $label) => ['id' => (int) $id, 'label' => (string) $label],
                    array_keys($available_profiles),
                    array_values($available_profiles)
                ),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $entities_json = json_encode(
                array_map(
                    static fn($id, $label) => ['id' => (int) $id, 'label' => (string) $label],
                    array_keys($available_entities),
                    array_values($available_entities)
                ),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            if (!is_string($profiles_json)) {
                $profiles_json = '[]';
            }
            if (!is_string($entities_json)) {
                $entities_json = '[]';
            }
            $remove_label_js = json_encode((string) t_ebenezerclone('Remove'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($remove_label_js)) {
                $remove_label_js = '"Remove"';
            }
            $yes_label_js = json_encode((string) __('Yes'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($yes_label_js)) {
                $yes_label_js = '"Yes"';
            }
            $no_label_js = json_encode((string) __('No'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($no_label_js)) {
                $no_label_js = '"No"';
            }
            $select_profile_warning_js = json_encode(
                (string) t_ebenezerclone('Select a profile before adding a new authorization row.'),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            if (!is_string($select_profile_warning_js)) {
                $select_profile_warning_js = '"Select a profile before adding a new authorization row."';
            }
            $auth_js = <<<JAVASCRIPT
(function() {
    var table = document.getElementById('ebz_clone_authorizations_table');
    var addBtn = document.getElementById('ebz_auth_add_row');
    var newProfile = document.getElementById('ebz_auth_new_profile');
    var newEntity = document.getElementById('ebz_auth_new_entity');
    var newRecursive = document.getElementById('ebz_auth_new_recursive');
    if (!table || !addBtn || !newProfile || !newEntity || !newRecursive) {
        return;
    }

    var profiles = $profiles_json || [];
    var entities = $entities_json || [];
    var removeLabel = $remove_label_js || 'Remove';
    var yesLabel = $yes_label_js || 'Yes';
    var noLabel = $no_label_js || 'No';
    var selectProfileWarning = $select_profile_warning_js || 'Select a profile before adding a new authorization row.';

    var findLabel = function(items, id, fallback) {
        for (var i = 0; i < items.length; i++) {
            if (String(items[i].id) === String(id)) {
                return String(items[i].label);
            }
        }
        return fallback;
    };

    var addRow = function(profileId, entityId, recursive) {
        var tbody = table.querySelector('tbody');
        var tr = document.createElement('tr');
        tr.className = 'ebz-auth-row';
        var recursiveInt = recursive ? 1 : 0;
        tr.setAttribute('data-profile-id', String(profileId));
        tr.setAttribute('data-entity-id', String(entityId));
        tr.setAttribute('data-recursive', String(recursiveInt));

        var tdEntity = document.createElement('td');
        tdEntity.className = 'ebz-auth-entity-label';
        tdEntity.textContent = findLabel(entities, entityId, '#' + entityId);
        tr.appendChild(tdEntity);

        var tdProfile = document.createElement('td');
        tdProfile.className = 'ebz-auth-profile-label';
        tdProfile.textContent = findLabel(profiles, profileId, '#' + profileId);
        tr.appendChild(tdProfile);

        var tdRecursive = document.createElement('td');
        tdRecursive.className = 'ebz-auth-recursive-label';
        tdRecursive.textContent = recursiveInt === 1 ? yesLabel : noLabel;
        tr.appendChild(tdRecursive);

        var tdRemove = document.createElement('td');
        var btnRemove = document.createElement('button');
        btnRemove.type = 'button';
        btnRemove.className = 'btn btn-outline-danger btn-sm ebz-auth-remove';
        btnRemove.textContent = removeLabel;
        tdRemove.appendChild(btnRemove);
        tr.appendChild(tdRemove);

        var rowKey = 'p' + profileId + '_e' + entityId + '_r' + (recursiveInt ? '1' : '0');
        var makeHidden = function(name, value) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = String(value);
            return input;
        };
        tr.appendChild(makeHidden('ebz_authorizations[' + rowKey + '][profiles_id]', profileId));
        tr.appendChild(makeHidden('ebz_authorizations[' + rowKey + '][entities_id]', entityId));
        tr.appendChild(makeHidden('ebz_authorizations[' + rowKey + '][is_recursive]', recursiveInt));

        tbody.appendChild(tr);
    };

    table.addEventListener('click', function(event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }
        if (target.classList.contains('ebz-auth-remove')) {
            var row = target.closest('.ebz-auth-row');
            if (row) {
                row.remove();
            }
        }
    });

    addBtn.addEventListener('click', function() {
        var profileId = parseInt(newProfile.value || '0', 10);
        if (!(profileId > 0)) {
            alert(selectProfileWarning);
            return;
        }

        var entityId = parseInt(newEntity.value || '0', 10);
        var recursive = parseInt(newRecursive.value || '0', 10) > 0;
        addRow(profileId, entityId, recursive);
        newProfile.value = '0';
        newRecursive.value = '0';
    });

    var form = table.closest('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (parseInt(newProfile.value || '0', 10) > 0) {
                alert(selectProfileWarning);
                event.preventDefault();
            }
        });
    }
})();
JAVASCRIPT;
            echo Html::scriptBlock($auth_js);
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

        if ($canedit) {
            echo "<tr class='tab_bg_1'><td colspan='2' class='center'>";
            echo Html::submit(_sx('button', 'Save'), [
                'name'    => 'update',
                'class'   => 'btn btn-primary',
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
        $is_new_install = empty($current);
        if (empty($current)) {
            $legacy = Config::getConfigurationValues('tr' . 'tclone');
            if (!empty($legacy)) {
                $current = $legacy;
                $is_new_install = false;
            }
        }
        if (!$is_new_install) {
            if (!array_key_exists(self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS, $current)) {
                $current[self::CONFIG_KEY_SHOW_HIDDEN_RELATED_TICKETS] = 1;
            }
            if (!array_key_exists(self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION, $current)) {
                $current[self::CONFIG_KEY_REQUIRE_GLPI_TICKET_CREATE_PERMISSION] = 0;
            }
        }
        Config::setConfigurationValues('ebenezerclone', array_merge($defaults, $current));
    }

    public static function uninstall()
    {
        Config::deleteConfigurationValues('ebenezerclone');
    }
}
