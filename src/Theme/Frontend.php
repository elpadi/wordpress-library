<?php

namespace WordpressLib\Theme;

abstract class Frontend
{

    protected static $_instance;

    protected $_data;
    protected $dir;

    public static function create(string $dir)
    {
        static::$_instance = new static($dir);
    }

    public static function instance()
    {
        if (!isset(static::$_instance)) {
            throw new() \BadMethodCallException("Instance must first be created.");
        }
        return static::$_instance;
    }

    protected function __construct($dir)
    {
        $this->dir = $dir;
        if (!defined('THEME_NAME')) {
            define('THEME_NAME', basename($dir));
        }

        static::ajaxActionJSON('get_asset_contents', function () {
            return $this->getAssetContents($_GET['path']);
        });
        
        add_filter('body_class', [$this, 'updateBodyClasses']);
    }

    public function updateBodyClasses($classes)
    {
        global $post;

        $classes[] = 'theme--' . THEME_NAME;
        $classes[] = 'user--' . get_current_user_id();

        if (is_page()) {
            $classes[] = "page--$post->post_name";
        }

        return $classes;
    }

    public function set($name, $data)
    {
        $this->_data[$name] = $data;
    }

    public function get($name, $key = '')
    {
        return isset($this->_data[$name]) ? ($key ? $this->_data[$name][$key] : $this->_data[$name]) : null;
    }

    public function getAssetContents(string $path)
    {
        if (strpos($path, './') !== false) {
            throw new() \InvalidArgumentException("Path must not contain directory dots.");
        }
        if (strpos($path, '/') === 0) {
            throw new() \InvalidArgumentException("Path cannot begin with a slash.");
        }

        $filename = "$this->dir/assets/$path";
        if (!is_readable($filename)) {
            throw new() \InvalidArgumentException("Could not find the specified file.");
        }

        return file_get_contents($filename);
    }

    public function svg($name, $dir = 'img')
    {
        return $this->getAssetContents("$dir/$name.svg");
    }

    abstract protected function enqueueFrontScripts($vars);
    abstract protected function enqueueAdminScripts($vars);
    abstract protected function enqueueLoginScripts($vars);

    public function jsVars($vars)
    {
        return array_merge_recursive($vars, [
            'URLS' => [
                'AJAX' => admin_url('admin-ajax.php'),
                'THEME' => get_stylesheet_directory_uri(),
            ],
            'THEME' => [
                'NAME' => THEME_NAME,
            ],
            'DEBUG' => WP_DEBUG ? 1 : 0,
            'USER' => ['ID' => get_current_user_id()],
            'IS_ADMIN' => is_admin(),
        ]);
    }

    public static function ajaxAction($tag, $fn)
    {
        add_action("wp_ajax_nopriv_" . THEME_NAME . "_$tag", $fn);
        add_action("wp_ajax_" . THEME_NAME . "_$tag", $fn);
    }

    public static function ajaxActionJSON($tag, $fn, $errorMsg = '')
    {
        static::ajaxAction($tag, function () use ($fn) {
            header('Content-type: application/json');
            try {
                $data = call_user_func($fn);
                if ($data !== false) {
                    wp_send_json_success($data);
                }
            } catch (\Exception $e) {
                wp_send_json_error(!WP_DEBUG && $errorMsg ? $errorMsg : $e->getMessage());
            }
            wp_send_json_error(!WP_DEBUG && $errorMsg ? $errorMsg : "Bad Request");
        });
    }

    public static function transientCache($key, $setter, $ttl)
    {
        if ($value = get_transient($key)) {
            return $value;
        }
        if ($value = call_user_func($setter)) {
            set_transient($key, $value, $ttl);
        }
        return $value;
    }

    public function enqueueScripts()
    {
        $dist_dir = get_stylesheet_directory() . '/assets/dist';
        $dist_url = get_stylesheet_directory_uri() . '/assets/dist';

        $env = IS_LOCAL ? 'dev' : 'prod';

        $assetFilename = function ($ext, $p) use ($env) {
            return "$p.$env.$ext";
        };
        $isValidAsset = function ($dist_dir, $filename) {
            return is_readable("$dist_dir/$filename") && filesize("$dist_dir/$filename");
        };

        $registerApp = function ($js_deps = []) use ($env, $dist_dir, $dist_url) {
            $name = THEME_NAME . '-app';
            $filename = "app.$env.js";
            wp_register_script($name, "$dist_url/$filename", $js_deps, filemtime("$dist_dir/$filename"));
            wp_localize_script($name, 'JS_ENV', apply_filters('js_vars', []));
            return $name;
        };

        $vars = get_defined_vars();

        add_filter('js_vars', [$this, 'jsVars']);

        add_action('wp_enqueue_scripts', function () use ($vars) {
            $this->enqueueFrontScripts($vars);
        }, 100);

        add_action('admin_enqueue_scripts', function () use ($vars) {
            $this->enqueueAdminScripts($vars);
        }, 100);

        add_action('login_enqueue_scripts', function () use ($vars) {
            $this->enqueueLoginScripts($vars);
        }, 100);
    }
}
