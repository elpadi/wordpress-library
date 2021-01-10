<?php

namespace Tome\Modules;

use Functional as F;
use Tome\Cover\Cover;
use Tome\Cover\Post;
use WordpressLib\Posts\CustomTaxonomy;
use WordpressLib\Admin\Notices;

class Modules extends \ArrayObject
{

    public $postTypes = ['post','page','attachment'];
    public $selected = null;

    protected static function createGlobalModuleTerm()
    {
        return (object)[
            'name' => sprintf('%s (%s)', get_option('blogname'), __('Global Module', 'tome')),
            'slug' => 'global',
        ];
    }

    public function __construct(Notices $notices, Cover $cover)
    {
        parent::__construct();

        $this->tax = new CustomTaxonomy('tome_module');

        $this->notices = $notices;
        $this->cover = $cover;

        add_action('admin_post_select_tome_module', [$this, 'select']);
        add_action('wp_ajax_module_page_order', [$this, 'updatePageOrder']);

        foreach (['add','edit','delete'] as $s) {
            add_action("wp_ajax_{$s}_tome_module", [$this, $s]);
        }

        add_filter('wp_link_pages', [$this, 'getPageNav'], 10, 2);

        if (is_admin()) {
            add_filter('tome-admin_theme_cover_template_vars', function ($vars) {
                $vars['p'] = $this->getCoverPost()->getPost();
                return $vars;
            });
        } else {
            add_action('pre_get_posts', [$this, 'updateModulePageQueryVars']);
        }
    }

    public function registerTaxonomy()
    {
        $this->tax->register($this->postTypes);
        add_action('init', [$this, 'init']);

        foreach ($this->postTypes as $t) {
            add_action("rest_insert_{$t}", [$this, 'onPostInserted'], 10, 3);
        }
    }

    public function init()
    {
        $this->fetchTerms();
    }

    /**
     * This function runs when the selected property has a value.
     */
    protected function onFrontEndModuleSelect()
    {
        if ($this->selected->slug == 'global') {
            add_filter('wp_list_pages_excludes', function ($excludes) {
                $excludes[] = $this->globalCoverPage->getId();
                return $excludes;
            });
        } else {
            add_filter('wp_page_menu_args', function ($args) {
                $args['include'] = F\pluck($this->getPosts('page'), 'ID');
                return $args;
            });
        }
    }

    public function getCoverPost()
    {
        if ($this->selected == null) {
            throw new() \BadMethodCallException("There is no selected module.");
        }
        return new Post($this->selected->name, $this->selected->slug, $this->cover->postType->slug);
    }

    public function fetchCoverSettings()
    {
        $post = $this->getCoverPost()->getPost();

        $blockMap = [
            'tome2/byline' => 'author',
            'core/paragraph' => 'description',
            'tome2/media' => 'background',
        ];
        $cover = (object)[
            'title' => $post->post_title,
            'author' => '',
        ];

        foreach (parse_blocks($post->post_content) as $b) {
            if (isset($blockMap[$b['blockName']])) {
                $prop = $blockMap[$b['blockName']];
                $cover->$prop = render_block($b);
            }
        }

        return $cover;
    }

    public function createPageNavLink($page, $className, $label)
    {
        return sprintf(
            '<a class="%s" href="%s"><span class="label">%s</span><span class="title">%s</span></a>',
            $className,
            get_the_permalink($page->ID),
            __($label, 'tome'),
            $page->post_title
        );
    }

    public function getPageNav($nav, $args)
    {
        global $post;
        $pages = $this->getPosts('page');
        foreach ($pages as $i => $p) {
            if ($p->ID == $post->ID) {
                if ($i > 0) {
                    $links[] = $this->createPageNavLink($pages[$i - 1], 'prev', 'Previous');
                }
                if ($i < count($pages) - 1) {
                    $links[] = $this->createPageNavLink($pages[$i + 1], 'next', 'Next');
                }
            }
        }
        return isset($links) ? '<nav class="chapter-nav"><div>' . implode('', $links) . '</div></nav>' : $nav;
    }

    public function updateModulePageQueryVars($query)
    {
        if ($query->is_main_query()) {
            if (!empty($query->query['name']) && !empty($query->query['tome_module'])) {
                add_filter('redirect_canonical', function ($redirect_url) {
                    return '';
                });
                $post = get_page_by_path($query->query['name'], OBJECT, ['post','page']);
                if ($post) {
                    $query->parse_query(
                        $post->post_type == 'post' ? 'name' : 'pagename'
                        . '=' .
                        $post->post_name
                    );
                }
            }
            if (!empty($query->query['tome_module'])) {
                $this->selected = $this->getTermBySlug($query->query['tome_module']);
            }
            if ($this->selected == null) {
                $this->selected = $this->getTermBySlug('global');
            }
            $this->onFrontEndModuleSelect();
        }
    }

    protected function addTermRewriteRules($term)
    {
        // module pages, original: /{page_slug}
        add_rewrite_rule("$term->slug/([^/]+)/?", 'index.php?tome_module=' . $term->slug . '&name=$matches[1]', 'top');
        // module cover (taxonomy archive), original: /tome_module/{term_slug}
        add_rewrite_rule("$term->slug/?", "index.php?tome_module=$term->slug", 'top');
    }

    protected function getTermBySlug($slug)
    {
        $slug = wp_unslash($slug);
        return F\first($this, function ($t) use ($slug) {
            return $t->slug == $slug;
        });
    }

    protected function fetchTerms()
    {
        $terms = $this->tax->getTerms();
        foreach ($terms as $t) {
            $this->addTermRewriteRules($t);
        }

        array_unshift($terms, static::createGlobalModuleTerm());

        $this->exchangeArray($terms);

        if (is_admin() && isset($_COOKIE['current_tome_module'])) {
            $this->selected = $this->getTermBySlug($_COOKIE['current_tome_module']);
        }

        do_action('tome_module_terms_fetched');
    }

    protected function onTermChange()
    {
        $this->fetchTerms();
        flush_rewrite_rules(false);
    }

    public function addPost($id)
    {
        return wp_set_post_terms($id, [$this->selected->name], $this->tax->slug, true);
    }

    public function onPostInserted($post, $request, $wasCreated)
    {
        if ($this->selected) {
            $this->addPost($post->ID);
        }
    }

    protected function setCurrentModule($termSlug)
    {
        return setcookie('current_tome_module', wp_unslash($termSlug), time() + YEAR_IN_SECONDS, '/');
    }

    protected function unsetCurrentModule()
    {
        return setcookie('current_tome_module', '', time() + YEAR_IN_SECONDS, '/');
    }

    public function select()
    {
        // 1. verify term
        if ($_POST['module-slug'] == 'global' || $this->tax->hasTerm($_POST['module-title'])) {
            $this->setCurrentModule($_POST['module-slug']);
        } else {
            $this->notices->flash->add('error', sprintf('Could not find module "%s".', $_POST['module-title']));
        }
        tome_admin_redirect('dashboard');
    }

    public function hasModules()
    {
        return $this->count() > 0;
    }

    public function unselectedErrorMessage($screenTitle)
    {
        return sprintf(__('Create or select a module above so you can manage its %s.', 'tome'), $screenTitle);
    }

    public function updatePageOrder()
    {
        $result = $this->updateMetaValue('page_order', $_POST['order']);
        if ($result === false || is_wp_error($result)) {
            if (\WP_DEBUG) {
                print_r($result);
            }
            $type = 'error';
            $msg = 'Could not update the page order.';
        } else {
            $type = 'success';
            $msg = 'Page order successfully updated.';
        }
        printf('<div class="notice notice-%s"><p>%s</p></div>', $type, $msg);
        wp_die();
    }

    protected function ajaxResponse($result, $success, $error)
    {
        $n = $this->selected->name;
        if ($result === false || is_wp_error($result)) {
            wp_send_json([
                'success' => false,
                'data' => $error,
                //'data' => \WP_DEBUG ? var_export($result, TRUE) : $error,
            ]);
        } else {
            wp_send_json_success($success);
        }
    }

    public function add()
    {
        $term = $this->tax->addTerm($_POST['module-title'], ['slug' => $_POST['module-slug']]);
        if (is_wp_error($term) == false) {
            $this->setCurrentModule($_POST['module-slug']);
        }
        $this->onTermChange();
        $this->ajaxResponse(
            $term,
            sprintf(__('The module %s was successfully created.', 'tome'), $n),
            'Could not create a new module.'
        );
    }

    public function edit()
    {
        $term = $this->tax->editTerm($this->selected->term_id, [
            'name' => $_POST['module-title'],
            'slug' => $_POST['module-slug'],
        ]);
        if (is_wp_error($term) == false) {
            $this->setCurrentModule($_POST['module-title']);
            $this->getCoverPost()->update($_POST['module-title'], $_POST['module-slug']);
        }
        $this->onTermChange();
        $this->ajaxResponse(
            $term,
            sprintf(__('The module %s was successfully edited.', 'tome'), $_POST['module-title']),
            'Could not edit the selected module.'
        );
    }

    public function delete()
    {
        $n = $this->selected->name;
        $term = $this->tax->deleteTerm($this->selected->term_id);
        if (is_wp_error($term) == false) {
            $this->unsetCurrentModule();
            $this->getCoverPost()->delete();
        }
        $this->onTermChange();
        $this->ajaxResponse(
            $term,
            sprintf(__('The module %s was successfully deleted.', 'tome'), $n),
            'Could not delete the selected module.'
        );
    }

    public function hasPostTypeSupport($postType)
    {
        return in_array($postType, $this->postTypes);
    }

    public function updateMetaValue($name, $value)
    {
        if ($this->selected == null) {
            throw new() \BadMethodCallException("There is no selected module.");
        }
        if ($this->selected->slug == 'global') {
            return update_option("global_module_$name", $value, false);
        }
        return update_term_meta($this->selected->term_id, $name, $value);
    }

    public function getMetaValue($name, $default = '')
    {
        if ($this->selected == null) {
            throw new() \BadMethodCallException("There is no selected module.");
        }
        if ($this->selected->slug == 'global') {
            return get_option("global_module_$name", $default);
        }
        $val = get_term_meta($this->selected->term_id, $name, true);
        return $val === '' ? $default : $val;
    }

    public function getPosts($postTypes, $skipSort = false)
    {
        $postTypes = (array)$postTypes;
        $args = [
            'post_type' => $postTypes,
            'posts_per_page' => -1,
            'post__not_in' => [$this->cover->globalCoverPage->getId()],
            'orderby' => 'date',
            'order' => 'ASC',
        ];
        if ($this->selected && $this->selected->slug != 'global' && F\every($postTypes, [$this, 'hasPostTypeSupport'])) {
            $args['tax_query'] = [
            [
                'taxonomy' => $this->tax->slug,
                'terms' => $this->selected->name,
                'field' => 'name',
            ],
            ];
        }
        $posts = get_posts($args);
        if ($skipSort == false && count($postTypes) == 1 && $postTypes[0] == 'page' && ($order_meta = $this->getMetaValue('page_order'))) {
            $order = explode(',', $order_meta[0]);
            $indexes = array_map(function ($p) use ($order) {
                return array_search($p->ID, $order);
            }, $posts);
            array_multisort($indexes, \SORT_NUMERIC, $posts);
        }
        return $posts;
    }
}
