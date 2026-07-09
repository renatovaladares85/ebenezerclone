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
    return true;
}

function plugin_ebenezerclone_is_super_admin_profile()
{
    return isset($_SESSION['glpiactiveprofile']['name'])
        && $_SESSION['glpiactiveprofile']['name'] === 'Super-Admin';
}

function plugin_ebenezerclone_pre_item_add_ticket($item)
{
    if (!($item instanceof Ticket)) {
        return true;
    }

    if (
        plugin_ebenezerclone_is_super_admin_profile()
        || !is_array($item->input)
        || empty($item->input)
        || empty($item->input['clone'])
    ) {
        return true;
    }

    $item->input = false;
    Session::addMessageAfterRedirect(__('You do not have permission to perform this action.'), false, ERROR);

    return false;
}
