<?php

use Glpi\Plugin\Hooks;

define('PLUGIN_EBENEZERCLONE_VERSION', '2.2.3');

// Minimal GLPI version, inclusive
define('PLUGIN_EBENEZERCLONE_MIN_GLPI_VERSION', '10.0.0');

// Maximum GLPI version, exclusive
define('PLUGIN_EBENEZERCLONE_MAX_GLPI_VERSION', '10.1.0');

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
    }
}

function plugin_version_ebenezerclone()
{
    return [
        'name'           => t_ebenezerclone('Ebenezer Clone'),
        'version'        => PLUGIN_EBENEZERCLONE_VERSION,
        'author'         => 'Renato Valadares',
        'homepage'       => '',
        'license'        => 'GPL v2+',
        'minGlpiVersion' => PLUGIN_EBENEZERCLONE_MIN_GLPI_VERSION,
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
