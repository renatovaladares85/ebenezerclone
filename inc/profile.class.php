<?php

if (!defined('GLPI_ROOT')) {
    die('Sorry. You cannot access directly to this file');
}

class PluginEbenezercloneProfile extends Profile
{
    public static $rightname = 'profile';

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() === 'Profile') {
            return self::createTabEntry(t_ebenezerclone('Ebenezer Clone'));
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() === 'Profile') {
            $profile = new self();
            $profile->showForm($item->getID());
        }
        return true;
    }

    public function showForm($profiles_id, $options = [])
    {
        if (!Session::haveRight('profile', READ)) {
            return false;
        }

        $canedit = Session::haveRight('profile', UPDATE);
        $profile = new Profile();
        $profile->getFromDB($profiles_id);

        echo "<form action='" . Profile::getFormUrl() . "' method='post'>";
        echo "<table class='tab_cadre_fixe'>";

        $general_rights = self::getGeneralRights();

        $profile->displayRightsChoiceMatrix(
            $general_rights,
            [
                'canedit'       => $canedit,
                'default_class' => 'tab_bg_2',
                'title'         => t_ebenezerclone('Ebenezer Clone')
            ]
        );

        $profile->showLegend();
        if ($canedit) {
            echo "<div class='spaced center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>\n";
        }

        echo "</table>";
        Html::closeForm();

        return true;
    }

    public static function getGeneralRights()
    {
        return [[
            'itemtype' => 'PluginEbenezercloneClone',
            'label'    => t_ebenezerclone('Clone ticket'),
            'field'    => self::getCloneRightName(),
        ]];
    }

    private static function getCloneRightName()
    {
        if (!class_exists('PluginEbenezercloneClone')) {
            include_once __DIR__ . '/clone.class.php';
        }
        return PluginEbenezercloneClone::$rightname;
    }

    public static function install()
    {
        return true;
    }

    public static function uninstall()
    {
        return true;
    }
}
