<?php
use Functional as F;

trait ImagesTrait {

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
		$images = empty($acf_images) ? [] : F\map(F\select($acf_images, function($acf_image) {
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
		$classes = apply_filters('slideshow_classes', array_merge(['slideshow'], $classes));
		extract($this->acfImagesInfo($field_name, $post_id));
		include(MU_PLUGIN_BASE_DIR.'/templates/slideshow.php');
	}

}
