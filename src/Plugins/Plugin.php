<?php

namespace WordpressLib\Plugins;

abstract class Plugin
{

    public function __construct()
    {
        add_action('init', [$this, 'init']);
    }

    public function init()
    {
    }
}
