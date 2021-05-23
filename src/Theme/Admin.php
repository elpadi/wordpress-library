<?php

namespace WordpressLib\Theme;

use WordpressLib\Posts\CustomTaxonomy;
use WP_Post;
use stdClass;
use InvalidArgumentException;

use function Functional\{
    first,
    ary,
    partial_any,
    map
};

use const Functional\…;

class Admin
{
    protected $siteTitle = '';

    public const DASHBOARD_CAPABILITY = 'publish_posts';

    public function __construct($slug, $title, $pluginDir, $templateVars = [])
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->pluginDir = $pluginDir;
        $this->templateVars = $templateVars;
        add_filter('admin_body_class', [$this, 'bodyClass']);
        add_action('init', [$this, 'init'], 20);
        add_action('admin_init', [$this, 'adminInit'], 20);
    }

    public function init()
    {
        $this->templateVars['dashicon'] = function ($name) {
            return sprintf('<i class="dashicons dashicons-%s"></i>', $name);
        };
    }

    public function adminInit()
    {
        $vars = apply_filters("{$this->slug}_append_template_vars", []);
        $this->templateVars = array_merge($this->templateVars, $vars);
    }

    public function getEditablePostTypes(): array
    {
        return ['post', 'page'];
    }

    public function isEditor()
    {
        global $post_type;
        if ($post_type && !in_array($post_type, $this->getEditablePostTypes())) {
            return false;
        }
        foreach (['post','post-new'] as $s) {
            if (strpos($_SERVER['REQUEST_URI'], "wp-admin/$s.php") !== false) {
                return true;
            }
        }
        return false;
    }

    public function isThemeScreen()
    {
        return $this->isEditor();
    }

    protected function getBodyClass()
    {
        return $this->slug;
    }

    public function bodyClass($classes)
    {
        $c = [];
        if ($this->isThemeScreen()) {
            $c[] = $this->getBodyClass();
        }
        return empty($c) ? $classes : $classes . ' ' . implode(' ', $c);
    }

    protected function getSubMenus()
    {
        return [];
    }

    public function getScreenPostTypeMap()
    {
        return ['blog' => 'post'];
    }

    public function getCapabilityFromScreen()
    {
        return self::DASHBOARD_CAPABILITY;
    }

    protected function getPostTypeNameFromScreen(): string
    {
        if (!isset($this->screenSlug)) {
            return '';
        }
        $map = $this->getScreenPostTypeMap();
        return $map[$this->screenSlug] ?? $this->screenSlug;
    }

    protected function getPostTypeFromScreen(): ?stdClass
    {
        $name = $this->getPostTypeNameFromScreen();
        return $name ? get_post_type_object($name) : null;
    }

    protected function getScreenTitle(?stdClass $postType = null): string
    {
        if (!$postType && !isset($this->screenSlug)) {
            return '';
        }
        return $postType->label ?? __(ucwords(str_replace('-', ' ', $this->screenSlug)), $this->siteTitle);
    }

    public function getScreenFromPostType($post_type)
    {
        $name = is_object($post_type) ? $post_type->name : "$post_type";
        return ($screen = array_search($name, $this->getScreenPostTypeMap())) !== false ? $screen : $name;
    }

    public function addOptions()
    {
        add_action('admin_menu', function () {
            $handle = "$this->slug-settings";
            add_menu_page(
                "$this->title Settings",
                $this->title,
                self::DASHBOARD_CAPABILITY,
                $handle,
                [$this, 'optionsHTML']
            );
            foreach ($this->getSubMenus() as $submenu) {
                extract($submenu);
                add_submenu_page(
                    $handle,
                    "$this->title $title",
                    $title,
                    self::DASHBOARD_CAPABILITY,
                    "$this->slug-$slug-settings",
                    [$this, 'optionsHTML']
                );
            }
        });
    }

    protected function wpScreenIdToSlug(string $wpScreenId): string
    {
        if (strpos($wpScreenId, "$this->slug-settings") !== false) {
            return 'dashboard';
        }
        if (preg_match("/$this->slug-(.*)-settings$/", $wpScreenId, $matches)) {
            return $matches[1];
        }
        throw new InvalidArgumentException("Admin screen '$wpScreenId' is invalid.");
    }

    protected function fetchScreenSlug(string $wpScreenId = ''): void
    {
        global $current_screen;
        $this->screenSlug = $this->wpScreenIdToSlug($wpScreenId ?: $current_screen->id);
    }

    public function optionsHTML()
    {
        $this->fetchScreenSlug();
        if (!current_user_can(self::DASHBOARD_CAPABILITY)) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        $this->template($this->screenSlug, true);
    }

    public function icon(string $name, bool $print = true): string
    {
        $handle = fopen("$this->pluginDir/assets/img/$name.svg", 'r');
        if (!$handle) {
            return '';
        }
        $html = '';
        while ($line = fgets($handle)) {
            if (strpos($line, '<?') !== false) {
                continue; // skip doctype
            }
            $html .= $line;
        }
        fclose($handle);
        if ($print) {
            echo $html;
        }
        return $html;
    }

    public function getTemplateLoader($dir, $vars = [])
    {
        return function () use ($dir, $vars) {
            extract(array_merge($this->templateVars, $vars));
            foreach (func_get_args() as $arg) {
                if (is_string($arg)) {
                    $_filenames[] = $arg;
                }
                if (is_array($arg)) {
                    extract($arg);
                }
            }
            foreach ($_filenames as $_filename) {
                include("$dir/$_filename.php");
            }
        };
    }

    public function template($templateName, $isGlobal = false, $isPartial = false, $vars = [])
    {
        global $current_user, $post;
        $adminTheme = $this;

        if ($templateName == 'listing' && isset($_GET['id'])) {
            $templateName = 'single';
        }

        $this->templateVars += get_defined_vars();

        if (is_admin()) {
            $this->templateVars['screenSlug'] = $this->screenSlug ?? '';
            $this->templateVars['post_type'] = $this->getPostTypeFromScreen();
            $this->templateVars['screenTitle'] = $this->getScreenTitle($this->templateVars['post_type']);
            $this->templateVars['p'] = isset($_GET['id']) && intval($_GET['id'])
                ? get_post($_GET['id'])
                : new WP_Post(new stdClass());
            $this->templateVars['_tpl'] = function ($dir, $vars = []) {
                return $this->getTemplateLoader($dir, $vars);
            };
            $this->templateVars['tpl'] = $this->getTemplateLoader($this->pluginDir . "/templates");
            $this->templateVars['icon'] = function ($name, $print = true) {
                return $this->icon($name, $print);
            };
            $this->templateVars['activeLanguage'] = apply_filters('wpml_current_language', 'en');
        }

        if ($isPartial) {
            extract(array_merge($this->templateVars, $vars));
        } else {
            extract(
                apply_filters(
                    "{$this->slug}_theme_{$this->screenSlug}_template_vars",
                    array_merge($this->templateVars, $vars)
                )
            );
        }

        if ($isGlobal) {
            echo '<style>html { padding-top: 0 !important; }</style>';
            include($this->pluginDir . "/templates/global/before-content.php");
        }

        $path = sprintf(
            '%s/templates/%s/%s.php',
            $this->pluginDir,
            $isPartial ? 'partial' : 'content',
            $isGlobal ? $this->screenSlug : $templateName
        );
        if (!is_readable($path)) {
            throw new InvalidArgumentException("Could not find the template at $templateName.");
        }
        include($path);

        if ($isGlobal) {
            include($this->pluginDir . "/templates/global/after-content.php");
        }
    }

    public function partialTemplateString($templateName, $vars = [])
    {
        ob_start();
        $this->template($templateName, false, true, $vars);
        return ob_get_clean();
    }

    public function registerAssets($fn)
    {
        add_action('admin_enqueue_scripts', function () use ($fn) {
            if ($this->isThemeScreen()) {
                call_user_func($fn);
            }
        });
    }

    public function registerLoginAssets($fn)
    {
        add_action('login_enqueue_scripts', $fn);
    }

    public function registerBlockAssets($fn)
    {
        add_action('enqueue_block_editor_assets', $fn);
    }

    public function getReadableTemplate(string $tokenizedPath, ...$names): string
    {
        return first(
            map($names, partial_any('str_replace', '#', …, $tokenizedPath)),
            ary('is_readable', 1)
        );
    }
}
