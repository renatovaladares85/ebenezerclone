<?php

include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isInstalled('ebenezerclone') || !$plugin->isActivated('ebenezerclone')) {
    Html::displayNotFoundError();
}

Session::checkRight('config', UPDATE);

Html::redirect(
    $CFG_GLPI['root_doc'] . '/front/config.form.php?forcetab=' .
    urlencode('PluginEbenezercloneConfig$1')
);
