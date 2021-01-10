<?php

namespace Tome\Pages;

use Tome\Plugins\Plugin;

class Pages extends Plugin
{

    public $pluginSlug = 'pages';
    public $screenTitle = 'Pages';

    public function __construct($frontAssets, $editorAssets)
    {
        parent::__construct();
        /*
        $this->postType = new PostType();
        $this->block = new Block('tome2', 'gallery', $frontAssets, $editorAssets);
         */
    }

    /*
    public function init() {
        $this->postType->register();
    }
     */

    /*
    public function getPosts() {
        return $this->postType->getPosts();
    }
     */

    public function addSubMenus($submenus)
    {
        $submenus['pages'] = 'Pages';
        $submenus['page'] = 'Page';
        return $submenus;
    }
}
