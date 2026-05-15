<?php

function plugin_ebenezerclone_install()
{
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        if (preg_match('/inc.(.+)\.class.php/', $filepath, $matches)) {
            $classname = 'PluginEbenezerclone' . ucfirst($matches[1]);
            include_once $filepath;
            if (method_exists($classname, 'install')) {
                $classname::install();
            }
        }
    }

    return true;
}

function plugin_ebenezerclone_uninstall()
{
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        if (preg_match('/inc.(.+)\.class.php/', $filepath, $matches)) {
            $classname = 'PluginEbenezerclone' . ucfirst($matches[1]);
            include_once $filepath;
            if (method_exists($classname, 'uninstall')) {
                $classname::uninstall();
            }
        }
    }

    return true;
}

function plugin_ebenezerclone_pre_item_update($item)
{
    if ($item instanceof Ticket) {
        return PluginEbenezercloneClone::guardAssignedActorsUpdate($item);
    }

    return true;
}
