<?php
namespace WordpressLib\Posts;

class CustomType {

	protected static $supports = ['title','editor','thumbnail'];

	public function __construct($slug, $singular='', $plural='') {
		if (empty($singular)) $singular = ucwords(str_replace(['_','-'], ' ', $slug));
		if (empty($plural)) $plural = $singular.'s';
		$this->slug = $slug;
		$this->singular = $singular;
		$this->plural = $plural;
		if (method_exists($this, 'updateRestFields')) {
			add_filter("rest_prepare_$this->slug", [$this, 'updateRestFields'], 10, 3);
		}
	}

	protected function createLabels() {
		return [
			'name'                => __($this->plural),
			'singular_name'       => __($this->singular),
			'menu_name'           => __($this->singular),
			'all_items'           => __("All $this->plural"),
			'view_item'           => __("View $this->singular"),
			'add_new_item'        => __("Add New $this->singular"),
			'add_new'             => __("Add $this->singular"),
			'edit_item'           => __("Edit $this->singular"),
			'update_item'         => __("Update $this->singular"),
			'search_items'        => __("Search $this->singular"),
			'not_found'           => __("No $this->singular found"),
			'not_found_in_trash'  => __("No $this->singular found in Trash"),
		];
	}

	protected function createSettings() {
		return [
			'public' => true,
			'labels' => $this->createLabels(),
			'menu_position' => 5,
			'has_archive' => true,
			'show_in_rest' => true,
			'rewrite' => [
				'slug' => $this->slug,
			],
		];
	}

	public function register($extra_supports=array(), $extra_settings=array()) {
		$settings = $this->createSettings();
		$settings['supports'] = array_merge(static::$supports, $extra_supports);
		register_post_type($this->slug, array_merge($settings, $extra_settings));
	}

	public function getIndexUrl() {
		return get_post_type_archive_link($this->slug);
	}

	public function getPosts() {
		$args = [
			'post_type' => $this->slug,
			'posts_per_page' => -1,
		];
		return get_posts($args);
	}

}
