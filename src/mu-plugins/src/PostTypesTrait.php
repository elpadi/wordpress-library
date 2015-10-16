<?php

trait PostTypesTrait {
	
	public static function postTypeURL($slug) {
		return get_post_type_archive_link(self::prefix($slug));
	}

	protected function registerPostType($slug, $singular='', $plural='', $extra_supports=array(), $extra_settings=array()) {
		if (empty($singular)) $singular = ucwords(substr($slug, 0, strlen($slug) - 1));
		if (empty($plural)) $plural = $singular.'s';
		register_post_type(self::prefix($slug), array_merge(array(
			'public' => true,
			'label' => $plural,
			'labels' => array(
				'singular_name' => $singular,
				'add_new_item' => "Add New $singular",
			),
			'supports' => array_merge(array('title','thumbnail'), $extra_supports),
			'menu_position' => 5,
			'has_archive' => true,
			'rewrite' => array(
				'slug' => $slug,
			),
		), $extra_settings));
	}

	protected function registerTaxonomy($slug, $obj_slug, $singular='', $plural='', $rewrite_slug='') {
		if (empty($singular)) $singular = ucwords(substr($slug, 0, strlen($slug) - 1));
		if (empty($plural)) $plural = $singular.'s';
		if (empty($rewrite_slug)) $rewrite_slug = $slug;
		register_taxonomy(self::prefix($slug), self::prefix($obj_slug), [
			'public' => true,
			'label' => $plural,
			'labels' => array(
				'singular_name' => $singular,
				'add_new_item' => "Add New $singular",
			),
			'query_var' => $slug,
			'rewrite' => array(
				'slug' => $rewrite_slug,
			),
		]);
		register_taxonomy_for_object_type(self::prefix($slug), self::prefix($obj_slug));
	}

}
