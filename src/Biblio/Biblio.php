<?php

namespace Tome\Biblio;

use Tome\Plugins\Plugin;

class Biblio extends Plugin
{

    public $pluginSlug = 'bibliography';
    public $screenTitle = 'Bibliography';

    public function __construct($frontAssets, $editorAssets, $modules)
    {
        parent::__construct();
        $this->postType = new() Reference\PostType();
        $this->dataBlock = new() Reference\Block\Data('tome2', 'reference-data', $frontAssets, $editorAssets);
        $this->outputBlock = new() Reference\Block\Output('tome2', 'reference-output', $frontAssets, $editorAssets);
        $this->biblioPage = new Page($modules);
        add_filter('the_content', [$this, 'contentScanAndUpdate'], 1000);
    }

    public function init()
    {
        $this->postType->register();
    }

    public function addSubMenus($submenus)
    {
        $submenus[$this->pluginSlug] = $this->screenTitle;
        return $submenus;
    }

    public function contentScanAndUpdate($c)
    {
        global $post;

        if (is_admin()) {
            return $c;
        }

        if (is_page() || is_singular()) {
            $refs = new ContentReferences();
            $refs->appendFromContent($post->post_content);
            $refs->transformTags();
            $refs->sort();
            if ($refs->count() && ($t = locate_template('template-parts/content/works-cited.php'))) {
                ob_start();
                include($t);
                $c .= ob_get_clean();
            }
        }

        return $c;
    }
}
