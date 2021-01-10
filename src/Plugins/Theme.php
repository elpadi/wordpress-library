<?php

namespace WordpressLib\Plugins;

abstract class Theme extends Plugin
{

    public function __construct()
    {
        parent::__construct();
        add_filter('admin_theme_submenus', [$this, 'addSubMenus']);
    }

    abstract public function addSubMenus($submenus);
}
