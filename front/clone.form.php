<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

if (isset($_POST['_clone'])) {
    $new_id = PluginEbenezercloneClone::cloneTicket($_POST);
    if ($new_id) {
        $ticket = new Ticket();
        Session::setActiveTab('Ticket', 'Ticket$1');
        Html::redirect($ticket->getFormURLWithID($new_id) . '&forcetab=Ticket$1');
    }

    Html::back();
}

Html::displayErrorAndDie('Lost!');
