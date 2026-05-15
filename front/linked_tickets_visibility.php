<?php

include('../../../inc/includes.php');

header('Content-Type: application/json; charset=UTF-8');

Session::checkLoginUser();

$ticket_id = (int) ($_GET['tickets_id'] ?? 0);
if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'invalid_ticket_id',
    ]);
    exit;
}

$ticket = new Ticket();
if (!$ticket->getFromDB($ticket_id) || !$ticket->can($ticket_id, READ)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'forbidden',
    ]);
    exit;
}

$all_links = Ticket_Ticket::getLinkedTicketsTo($ticket_id, false);
$visible_links = Ticket_Ticket::getLinkedTicketsTo($ticket_id, true);
$missing_links = array_diff_key($all_links, $visible_links);

$items = [];
foreach ($missing_links as $relation_id => $link_data) {
    $related_ticket_id = (int) ($link_data['tickets_id'] ?? 0);
    if ($related_ticket_id <= 0) {
        continue;
    }

    $items[] = [
        'relation_id' => (int) $relation_id,
        'tickets_id'  => $related_ticket_id,
        'link_label'  => (string) Ticket_Ticket::getLinkName(
            (int) ($link_data['link'] ?? 0),
            isset($link_data['tickets_id_1']),
            false
        ),
        'url'         => Ticket::getFormURLWithID($related_ticket_id),
        'title'       => sprintf(t_ebenezerclone('Linked ticket #%1$s'), $related_ticket_id),
    ];
}

echo json_encode([
    'ok'            => true,
    'items'         => $items,
    'total_links'   => count($all_links),
    'visible_links' => count($visible_links),
]);

