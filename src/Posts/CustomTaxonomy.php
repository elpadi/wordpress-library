<?php

namespace WordpressLib\Posts;

class CustomTaxonomy
{

    protected static $supports = ['title','thumbnail'];

    public function __construct($slug, $singular = '', $plural = '', $rewrite = '')
    {
        if (empty($singular)) {
            $singular = ucwords(implode(' ', explode('_', $slug)));
        }
        if (empty($plural)) {
            $plural = $singular . 's';
        }
        $this->slug = $slug;
        $this->singular = $singular;
        $this->plural = $plural;
        if (empty($rewrite)) {
            $this->rewrite = $slug;
        }
    }

    protected function createLabels()
    {
        return [
            'name' => __($this->plural),
            'singular_name' => __($this->singular),
            'menu_name' => __($this->singular),
            'all_items' => __("All $this->plural"),
            'edit_item' => __("Edit $this->singular"),
            'view_item' => __("View $this->singular"),
            'update_item' => __("Update $this->singular"),
            'add_new_item' => __("Add New $this->singular"),
            'new_item_name' => __("New $this->singular Name"),
            'parent_item' => __("Parent $this->singular"),
            'parent_item' => __("Parent $this->singular:"),
            'search_items' => __("Search $this->plural"),
            'popular_items' => __("Popular $this->plural"),
            'separate_items_with_commas' => __("Separate $this->plural with commas"),
            'add_or_remove_items' => __("Add or remove $this->plural"),
            'choose_from_most_used' => __("Choose from the most used $this->plural"),
            'not_found' => __("No $this->plural found"),
        ];
    }
            
    protected function createSettings()
    {
        return [
            'labels' => $this->createLabels(),
            'query_var' => $this->slug,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array(
                'slug' => $this->rewrite,
            ),
        ];
    }

    public function register($post_type)
    {
        add_action('init', function () use ($post_type) {
            register_taxonomy($this->slug, $post_type, $this->createSettings());
            foreach ((array)$post_type as $t) {
                register_taxonomy_for_object_type($this->slug, $t);
            }
        });
    }

    public function addTerm($title, $args = [])
    {
        return wp_insert_term($title, $this->slug, $args);
    }

    public function editTerm($term_id, $args = [])
    {
        return wp_update_term($term_id, $this->slug, $args);
    }

    public function deleteTerm($term_id)
    {
        return wp_delete_term($term_id, $this->slug);
    }

    public function hasTerm($term)
    {
        return term_exists($term, $this->slug);
    }

    public function getTerms($postIDs = [])
    {
        $terms = get_terms(['taxonomy' => $this->slug, 'hide_empty' => false, 'object_ids' => $postIDs]);
        if (is_wp_error($terms)) {
            if (\WP_DEBUG) {
                var_dump($this, $terms->get_error_messages());
                exit();
            }
            return [];
        }
        return $terms;
    }
}
