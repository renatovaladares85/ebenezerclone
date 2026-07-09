<?php

use Glpi\Plugin\Hooks;

define('PLUGIN_EBENEZERCLONE_VERSION', '3.1.26');

// Minimal GLPI version, inclusive
define('PLUGIN_EBENEZERCLONE_MIN_GLPI_VERSION', '10.0.0');

// Maximum GLPI version, exclusive
define('PLUGIN_EBENEZERCLONE_MAX_GLPI_VERSION', '10.0.99');

function plugin_init_ebenezerclone()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['ebenezerclone'] = true;
    $PLUGIN_HOOKS['config_page']['ebenezerclone'] = 'front/config.form.php';

    $plugin = new Plugin();
    if ($plugin->isActivated('ebenezerclone')) {
        Plugin::registerClass('PluginEbenezercloneClone', ['addtabon' => 'Ticket']);
        Plugin::registerClass('PluginEbenezercloneConfig', ['addtabon' => 'Config']);

        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['ebenezerclone'][] = 'js/ebenezerclone.js';
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['ebenezerclone'][] = 'js/restrict_native_clone_actions.js.php';
        $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['ebenezerclone'] = [
            'Ticket' => 'plugin_ebenezerclone_pre_item_add_ticket',
        ];
    }
}

function plugin_version_ebenezerclone()
{
    return [
        'name'           => t_ebenezerclone('Ebenezer Clone'),
        'version'        => PLUGIN_EBENEZERCLONE_VERSION,
        'author'         => 'Renato Valadares',
        'homepage'       => 'https://github.com/renatovaladares85/ebenezerclone',
        'license'        => 'GPL v2+',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_EBENEZERCLONE_MIN_GLPI_VERSION,
                'max' => PLUGIN_EBENEZERCLONE_MAX_GLPI_VERSION,
            ]
        ]
    ];
}

function plugin_ebenezerclone_check_prerequisites()
{
    if (version_compare(GLPI_VERSION, PLUGIN_EBENEZERCLONE_MIN_GLPI_VERSION, 'lt')) {
        echo 'This plugin requires GLPI >= ' . PLUGIN_EBENEZERCLONE_MIN_GLPI_VERSION;
        return false;
    }
    if (version_compare(GLPI_VERSION, PLUGIN_EBENEZERCLONE_MAX_GLPI_VERSION, 'ge')) {
        echo 'This plugin requires GLPI < ' . PLUGIN_EBENEZERCLONE_MAX_GLPI_VERSION;
        return false;
    }
    return true;
}

function plugin_ebenezerclone_check_config($verbose = false)
{
    return true;
}

function plugin_ebenezerclone_changeProfile()
{
    return true;
}

function t_ebenezerclone($str)
{
    return __($str, 'ebenezerclone');
}
