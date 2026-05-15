<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
header('Content-Type: application/json; charset=UTF-8');

$tickets_id = (int) ($_GET['tickets_id'] ?? 0);
if ($tickets_id <= 0) {
    echo json_encode([
        'ok' => false,
        'can_edit' => false,
        'actor_permissions' => [
            'requester' => false,
            'observer' => false,
            'assign' => false,
        ],
        'should_lock_properties' => false,
        'property_lock_fields' => [],
        'can_use_ticket_clone_action' => PluginEbenezercloneClone::canUseTicketCloneActionInCurrentProfile(),
        'can_use_massive_clone' => PluginEbenezercloneClone::canUseMassiveCloneActionInCurrentProfile(),
    ]);
    exit;
}

$ticket = new Ticket();
if (!$ticket->getFromDB($tickets_id)) {
    echo json_encode([
        'ok' => false,
        'can_edit' => false,
        'actor_permissions' => [
            'requester' => false,
            'observer' => false,
            'assign' => false,
        ],
        'should_lock_properties' => false,
        'property_lock_fields' => [],
        'can_use_ticket_clone_action' => PluginEbenezercloneClone::canUseTicketCloneActionInCurrentProfile(),
        'can_use_massive_clone' => PluginEbenezercloneClone::canUseMassiveCloneActionInCurrentProfile(),
    ]);
    exit;
}

$ticket->check($tickets_id, READ);
$actor_permissions = PluginEbenezercloneClone::getActorEditPermissions($ticket);
$can_edit = !empty($actor_permissions['requester'])
    || !empty($actor_permissions['observer'])
    || !empty($actor_permissions['assign']);
$should_lock_properties = PluginEbenezercloneClone::shouldLockPropertiesByPlugin($ticket);

echo json_encode([
    'ok' => true,
    'can_edit' => $can_edit,
    'actor_permissions' => $actor_permissions,
    'should_lock_properties' => $should_lock_properties,
    'property_lock_fields' => PluginEbenezercloneClone::getLockedPropertyFieldsForCurrentUser($ticket),
    'can_use_ticket_clone_action' => PluginEbenezercloneClone::canUseTicketCloneActionInCurrentProfile(
        (int) $ticket->getField('entities_id')
    ),
    'can_use_massive_clone' => PluginEbenezercloneClone::canUseMassiveCloneActionInCurrentProfile(
        (int) $ticket->getField('entities_id')
    ),
]);
