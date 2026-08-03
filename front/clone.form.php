<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Html::displayErrorAndDie(__('Invalid request.'));
}

if (isset($_POST['_clone'])) {
    // GLPI validates and consumes the CSRF token while loading inc/includes.php.
    $new_id = PluginEbenezercloneClone::cloneTicket($_POST);
    if ($new_id) {
        $ticket = new Ticket();
        Session::setActiveTab('Ticket', 'Ticket$1');
        Html::redirect($ticket->getFormURLWithID($new_id) . '&forcetab=Ticket$1');
    }

    Html::back();
}

Html::displayErrorAndDie(__('Invalid request.'));
