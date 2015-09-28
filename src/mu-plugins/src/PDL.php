<?php
use Functional as F;
use Cypress\Curry as C;
defined('ABSPATH') || exit;

class PDL extends App {

	const HOME_POST_ID = 2;
	
	protected static $SITE_PREFIX = 'pdl_';

	protected function siteInit() {
		$this->registerPostType('gallery', 'Gallery', 'Galleries', [], ['menu_icon' => 'dashicons-images-alt', 'supports' => ['title']]);
		$this->registerPostType('units', '', '', [], ['menu_icon' => 'dashicons-admin-multisite', 'supports' => ['title']]);
		//$this->registerTaxonomy('floors','units');
		add_filter('tile_content', array($this, 'tileContent'), 10, 2);
	}

	public function theme_init() {
		if (is_front_page()) {
			$root_menu_items = F\select(wp_get_nav_menu_items('main-menu'), function($p) { return $p->menu_item_parent == 0; });
			$this->home_sections = F\map(get_posts(['post_type' => 'page', 'include' => F\pluck($root_menu_items, 'object_id'), 'orderby' => 'post__in']), array($this, 'parseHomeSection'));
		}
	}

	public function siteSettings() {
		add_settings_field('main_gallery_name', 'Select Main Gallery', C\curry(array($this, 'postsDropdownField'), 'gallery', 'main_gallery_name', get_option('main_gallery_name', '')), 'media', 'main_gallery');
		register_setting('media', 'main_gallery_name');
		add_settings_section('main_gallery', 'Main Gallery', function() {
			echo '<p>The gallery that opens from the top left gallery button.</p>';
		}, 'media');
	}
	
	public function getBreadcrumb() {
		global $post;
		if (!$post->post_parent || !($headline = get_field('breadcrumb_headline', $post->post_parent))) return [];
		$title = get_the_title($post);
		return [$headline, $title];
	}

	public function breadcrumb() {
		$parts = $this->getBreadcrumb();
		if (empty($parts)) return '';
		$headline = $parts[0];
		$title = $parts[1];
		include(MU_PLUGIN_BASE_DIR.'/templates/breadcrumb.php');
	}
	
	public function parseHomeSection($post) {
		return array(
			'title' => get_field('home_title', $post->ID),
			'menu_title' => get_the_title($post),
			'slug' => $post->post_name,
			'background' => $this->acfResponsiveImage('home_image', $post->ID),
			'description' => apply_filters('the_content', $post->post_content),
			'headline' => get_field('home_headline', $post->ID),
			'url' => get_the_permalink($post),
			'action' => get_field('home_action', $post->ID),
		);
	}

}
