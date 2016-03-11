<?php
use Functional as F;
defined('ABSPATH') || exit;

abstract class App extends Site implements SingletonInterface, ImagesInterface, GridInterface, TilesInterface, PaginationInterface, PostQueriesInterface {

	/**
	 * use SingletonTrait;
	 */
	private static $_instance;

	public static function instance() {
		if (!self::$_instance) {
			$class = get_called_class();
			self::$_instance = new $class();
		}
		return self::$_instance;
	}

	/**
	 * use PostsTrait;
	 */
	public static function normalizeShortcodeContent($content) {
		// remove starting empty p, ending emtpy p, or break
		if (strpos($content, '</p>') === 0) $content = substr($content, 4);
		if (strpos($content, '<br>') === 0) $content = substr($content, 4);
		if (substr($content, -3) === '<p>') $content = substr($content, 0, strlen($content) - 3);
		$content = trim($content);
		return $content;
	}

	public function getPageContent($name) {
		global $post;
		$post = get_page_by_path($name);
		if (!$post) return '';
		setup_postdata($post);
		ob_start();
		get_template_part('content','page');
		return ob_get_clean();
	}

	public function getChildPages($id=0, $args=array()) {
		if (!$id) $id = get_the_ID();
		return get_posts(array_merge(array('post_type' => 'page', 'post_parent' => $id, 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'), $args));
	}


	/**
	 * use PostTypesTrait;
	 */
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
		register_taxonomy(self::prefix($slug), self::prefix($obj_slug), array(
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
		));
		register_taxonomy_for_object_type(self::prefix($slug), self::prefix($obj_slug));
	}

	/**
	 * use PaginationTrait;
	 */
	public function pagination($total=-1, $spread=-1) {
		global $wp_query;
		$big = 999999999; // need an unlikely integer
		$current = max( 1, get_query_var('paged') );
		if ($total < 1) $total = $wp_query->max_num_pages;
		if ($spread < 1) $spread = self::DEFAULT_PAGINATION_SPREAD;
		$spread_left = min($current - 1, $spread * 2);
		$spread_right = min($current + $spread * 2, $total) - $current;
		if ($spread_left + $spread_right > 4) {
			if ($spread_left >= 2 && $spread_right >= 2) $spread_left = $spread_right = 2;
			elseif ($spread_left < 2) $spread_right = 4 - $spread_left;
			else $spread_left = 4 - $spread_right;
		}
		$pages = range($current - $spread_left, $current + $spread_right);
		$links = array_map(function($n) use ($current) {
			return '<li>'.($n == $current ? "<span>$current</span>" : sprintf('<a href="%s">%s</a>', get_pagenum_link($n), $n)).'</li>';
		}, $pages);
		$out = '<nav class="pagination">';
		$out .= sprintf('<a class="edge edge--left" href="%s">First</a>', get_pagenum_link(1));
		$out .= sprintf('<ul class="nav-menu">%s</ul>', implode('', $links));
		$out .= sprintf('<a class="edge edge--right" href="%s">Last</a>', get_pagenum_link($total));
		$out = '</nav>';
		return $out;
	}

	/**
	 * use PostQueriesTrait;
	 */
	protected static function attachmentIdFrom($key, $meta_key='', $parentTable='posts') {
		global $wpdb;
		if (empty($meta_key)) $meta_key = $key;
		if ($meta_key === 'thumb') $meta_key = '_thumbnail_id';
		$key = "{$key}_id";
		return "LEFT JOIN `$wpdb->postmeta` $key ON $parentTable.`ID`=$key.`post_id` AND $key.`meta_key`='$meta_key'";
	}

	protected static function attachmentDataFrom($key) {
		global $wpdb;
		$id_key = "{$key}_id";
		$val_key = "{$key}_data";
		return "LEFT JOIN `$wpdb->postmeta` $val_key ON $id_key.`meta_value`=$val_key.`post_id` AND $val_key.`meta_key`='_wp_attachment_metadata'";
	}

	protected static function attachmentFileFrom($key) {
		global $wpdb;
		$id_key = "{$key}_id";
		$val_key = "{$key}_file";
		return "LEFT JOIN `$wpdb->postmeta` $val_key ON $id_key.`meta_value`=$val_key.`post_id` AND $val_key.`meta_key`='_wp_attached_file'";
	}

	protected static function attachmentFileSelect($key) {
		return "{$key}_file.`meta_value` AS {$key}_file";
	}

	protected static function attachmentDataSelect($key) {
		return "{$key}_data.`meta_value` AS {$key}_serialized";
	}

	protected static function repeaterPostIdFrom($repeater_item_key, $post_key) {
		global $wpdb;
		$table = "{$repeater_item_key}_post_id";
		return "LEFT JOIN $wpdb->postmeta $table ON posts.`ID`=$table.`post_id` AND $table.`meta_key`='{$repeater_item_key}_{$post_key}'";
	}

	protected static function repeaterPostFrom($repeater_item_key, $post_key) {
		global $wpdb;
		$table = "{$repeater_item_key}_post";
		return "LEFT JOIN $wpdb->posts $table ON $table.`ID`={$table}_id.`meta_value`";
	}

	protected static function repeaterPostColumnSelect($repeater_item_key, $col) {
		return "{$repeater_item_key}_post.`$col` AS {$repeater_item_key}_$col";
	}

	protected static function textFieldSelect($key) {
		return "$key.`meta_value` AS $key";
	}

	protected static function textFieldFrom($key) {
		global $wpdb;
		return "LEFT JOIN `$wpdb->postmeta` $key ON posts.`ID`=$key.`post_id` AND $key.`meta_key`='$key'";
	}

	public static function customPostsQuery($ids, $fields, $order='DESC') {
		global $wpdb;

		$selects = array("posts.*");
		$froms = array("`$wpdb->posts` posts");

		if (isset($fields['text'])) foreach ($fields['text'] as $key) {
			$selects[] = self::textFieldSelect($key);
			$froms[] = self::textFieldFrom($key);
		}
		if (isset($fields['attachments'])) foreach ($fields['attachments'] as $key) {
			$selects[] = self::attachmentFileSelect($key);
			$selects[] = self::attachmentDataSelect($key);
			$froms[] = self::attachmentIdFrom($key);
			$froms[] = self::attachmentDataFrom($key);
			$froms[] = self::attachmentFileFrom($key);
		}
		if (isset($fields['repeaters'])) foreach ($fields['repeaters'] as $repeater_key => $repeater_fields) {
			$selects[] = "3 AS {$repeater_key}_count";
			for ($i = 0; $i < 3; $i++) {
				$index_key = "{$repeater_key}_{$i}";
				$froms[] = self::repeaterPostIdFrom($index_key, $repeater_fields['post']);
				$froms[] = self::repeaterPostFrom($index_key, $repeater_fields['post']);
				foreach ($repeater_fields['post_columns'] as $col) {
					$selects[] = self::repeaterPostColumnSelect($index_key, $col);
				}
				if (isset($repeater_fields['text'])) foreach ($repeater_fields['text'] as $key) {
					$_key = "{$repeater_key}_{$i}_{$key}";
					$selects[] = self::textFieldSelect($_key);
					$froms[] = self::textFieldFrom($_key);
				}
				if (isset($repeater_fields['attachments'])) foreach ($repeater_fields['attachments'] as $key) {
					$item_key = "{$repeater_key}_{$i}_{$key}";
					$froms[] = self::attachmentIdFrom($item_key, $key, "{$repeater_key}_{$i}_post");
					$selects[] = self::attachmentDataSelect($item_key);
					$selects[] = self::attachmentFileSelect($item_key);
					$froms[] = self::attachmentDataFrom($item_key);
					$froms[] = self::attachmentFileFrom($item_key);
				}
			}
		}

		$sql = sprintf('SELECT %s FROM %s WHERE posts.`ID` IN (%s) ORDER BY posts.`ID` %s', implode(',', $selects), implode(' ', $froms), implode(',', $ids), $order);
		return $wpdb->get_results($sql);
	}

	/**
	 * use ImagesTrait;
	 */
	public static function blankSrc() {
		echo get_template_directory_uri()."/img/blank.gif";
	}

	public function getResponsiveWidths() {
		return apply_filters('responsive_widths', array_map(function($s) { return intval($s); }, explode(',', self::RESPONSIVE_WIDTHS)));
	}

	protected function getResponsiveAttributesFromPaths($paths_by_width) {
		$resp_widths = $this->getResponsiveWidths();
		$new_path_by_widths = array();
		ksort($paths_by_width, SORT_NUMERIC);
		foreach ($paths_by_width as $width => $path) {
			foreach ($resp_widths as $w) if ($width <= $w) {
				$new_path_by_widths[$w] = $path;
				break;
			}
		}
		foreach ($new_path_by_widths as $width => $path) $srcs[] = "$path {$width}w";
		$src = substr($srcs[0], 0, strpos($srcs[0], ' '));
		return compact('src','srcs');
	}

	protected function getPathsFromAcfImage($acf_img) {
		$paths_by_width = array();
		foreach ($acf_img['sizes'] as $key => $val) {
			if (($pos = strpos($key, '-width')) !== FALSE) {
				$paths_by_width[$val] = $acf_img['sizes'][substr($key, 0, $pos)];
			}
		}
		$paths_by_width[$acf_img['width']] = $acf_img['url'];
		return $paths_by_width;
	}

	protected function getPathsFromAttachment($data) {
		$paths_by_width = array();
		$upload_dir = wp_upload_dir();
		$path = $upload_dir['baseurl'].'/'.substr($data['file'], 0, strrpos($data['file'], '/'));
		foreach ($data['sizes'] as $size_info) {
			$paths_by_width[$size_info['width']] = "$path/$size_info[file]";
		}
		$paths_by_width[$data['width']] = "$upload_dir[baseurl]/$data[file]";
		return $paths_by_width;
	}

	public function getImageAttributesFromAcfImage($acf_img) {
		$paths = $this->getPathsFromAcfImage($acf_img);
		$atts = $this->getResponsiveAttributesFromPaths($paths);
		$atts['title'] = isset($acf_img['title']) ? $acf_img['title'] : '';
		$atts['description'] = isset($acf_img['description']) ? $acf_img['description'] : '';
		return $atts;
	}

	protected function getResponsiveAttributesFromAttachment($data) {
		$paths = $this->getPathsFromAttachment($data);
		return $this->getResponsiveAttributesFromPaths($paths);
	}

	protected function createResponsiveImage($atts, $classes, $sizes=array('100vw')) {
		extract($atts);
		return sprintf('<img class="wp-image responsive-image %s" alt="" src="%s" srcset="%s" sizes="%s">', implode(' ', $classes), $src, implode(',', $srcs), implode(',', $sizes));
	}

	public function acfResponsiveImage($field_name_or_field, $post_id=0, $classes=array(), $sizes=array('100vw')) {
		$acf_image = is_string($field_name_or_field) ? get_field($field_name_or_field, $post_id) : $field_name_or_field;
		if (!is_array($acf_image)) return '';
		return $this->createResponsiveImage($this->getImageAttributesFromAcfImage($acf_image), $classes, $sizes);
	}

	public function responsiveFeaturedImage($post_id=0, $classes=array()) {
		if (!$post_id) $post_id = get_the_ID();
		$thumb_id = get_post_thumbnail_id($post_id);
		if (!$thumb_id || !($thumb_data = wp_get_attachment_metadata($thumb_id))) {
			trigger_error("Post image not loaded for ID $post_id.", E_USER_WARNING);
			return '';
		}
		return $this->responsiveAttachedImage($thumb_data, $classes);
	}

	public function responsiveAttachedImage($thumb_data, $classes=array()) {
		return $this->createResponsiveImage($this->getResponsiveAttributesFromAttachment($thumb_data), $classes);
	}

	protected function acfImagesInfo($field_name, $post_id) {
		$acf_images = get_field($field_name, $post_id);
		$html_id = $post_id ? "$field_name-$post_id" : $field_name;
		$images = empty($acf_images) ? array() : F\map(F\select($acf_images, function($acf_image) {
			return strpos($acf_image['mime_type'], 'image') === 0;
		}), array($this, 'getImageAttributesFromAcfImage'));
		$first = count($images) ? $images[0] : NULL;
		return compact('html_id','images','first');
	}

	public function acfGallery($name, $print=true, $gallery_title='') {
		$post = get_page_by_path($name, OBJECT, 'pdl_gallery');
		if (!$post) return '';

		if (empty($gallery_title)) $gallery_title = get_the_title($post);
		extract($this->acfImagesInfo('gallery_images', $post->ID));
		if ($print) include(MU_PLUGIN_BASE_DIR.'/templates/gallery.php');
		else {
			ob_start();
			include(MU_PLUGIN_BASE_DIR.'/templates/gallery.php');
			return ob_get_clean();
		}
	}

	public function acfSlideshow($field_name, $post_id=0, $classes=array(), $show_buttons = true) {
		$classes = apply_filters('slideshow_classes', array_merge(array('slideshow'), $classes));
		extract($this->acfImagesInfo($field_name, $post_id));
		include(MU_PLUGIN_BASE_DIR.'/templates/slideshow.php');
	}

	
	/**
	 * use GridTrait;
	 */
	protected function getGridItemClasses($colcount, $index, $count) {
		$center = $colcount / 2;
		$col = $index % $colcount;
		$row = floor($index / $colcount);
		$last_row = floor(($count - 1) / $colcount);

		$classes[] = $col <= $center - 1 ? 'left' : ($colcount % 2 !== 0 && $col == floor($center) ? 'center' : 'right');
		if ($col === 0) $classes[] = 'first-col';
		if ($col === $colcount - 1) $classes[] = 'last-col';
		if ($row == 0) $classes[] = 'first-row';
		if ($row == $last_row) $classes[] = 'last-row';

		return array_map(function($s) { return "grid__item--$s"; }, $classes);
	}
	
	/*
	protected function grid_item_class($classes, $colcount, $index=-1, $count=-1, $prefix='grid__item') {
		global $wp_the_query;
		if ($index === -1) {
			$index = $wp_the_query->current_post;
			$count = $wp_the_query->post_count;
		}
		return array_merge($classes, $this->getGridItemClasses($colcount, $index, $count, $prefix));
	}
	 */
	
	protected function navItemToGridImageItem($link, $index, $count, $colcount) {
		$classes = $this->getGridItemClasses($colcount, $index, $count);
		$url = $link->url;
		$image = $this->acfResponsiveImage('intro_image', $link->object_id);
		$title = $link->title;
		return apply_filters('grid_image_item', compact('classes','url','image','title'), $link);
	}

	protected function postToGridImageItem($post, $index, $count, $colcount) {
		$classes = $this->getGridItemClasses($colcount, $index, $count);
		$url = get_the_permalink($post);
		$image = $this->acfResponsiveImage('intro_image', $post->ID);
		$title = get_the_title($post);
		return apply_filters('grid_image_item', compact('classes','url','image','title'), $post);
	}

	public function subsectionsGrid($id='') {
		$subsections_menu = get_field('subsections');
		if (!$subsections_menu) return;
		$container_classes = array('grid--image','grid--subsections');
		if (!empty($id)) $container_classes[] = "grid--$id";
		$colcount = 3;
		$sections = wp_get_nav_menu_items($subsections_menu);
		$count = count($sections);
		foreach ($sections as $i => $post) $items[] = $this->navItemToGridImageItem($post, $i, $count, $colcount);
		include(MU_PLUGIN_BASE_DIR.'/templates/grid.php');
	}

	/**
	 * use TilesTrait;
	 */
	public function tiles($info_field_name, $media_field_name) {
		$tiles = new TilesInfo(get_field($info_field_name), get_field($media_field_name));
		include(MU_PLUGIN_BASE_DIR.'/templates/tiles.php');
	}

	public function tileContent($content, $type) {
		$fn = "{$type}Content";
		return $this->$fn($content);
	}

	protected function breakContent() {
		return '';
	}

	protected function imageContent($acfImage) {
		return self::instance()->acfResponsiveImage($acfImage);
	}

	protected function videoContent($acfVideo) {
		return sprintf('<video autoplay loop src="%s"></video>', $acfVideo['url']);
	}

	protected function pageContent($post) {
		return sprintf('<h2>%s</h2><hr><div>%s</div>', get_the_title($post), apply_filters('the_content', $post->post_content));
	}

	/**
	 * End traits
	 */

	private function __construct() {
		add_action('wp', array($this, 'themeInit'));
		add_action('admin_init', array($this, 'adminInit'));
		$this->checkAdminBarStatus();
		$this->siteInit();
	}

	public function adminInit() {
		add_filter('acf/fields/relationship/query', function($options, $field, $the_post) {
			$options['post_status'] = 'publish';
			return $options;
		}, 10, 3);
		$this->siteSettings();
	}

}
