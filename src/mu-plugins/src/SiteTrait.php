<?php

trait SiteTrait {

	protected static $SITE_PREFIX = 'site_';

	public static function prefix($s) {
		return static::$SITE_PREFIX.$s;
	}

	protected function siteInit() {
	}

	public function siteSettings() {
	}
	
	public function theme_init() {
	}

	public function postsDropdownField($post_type, $name, $value) {
		$posts = get_posts(['post_type' => self::prefix($post_type), 'posts_per_page' => -1]);
		$options = array_combine(F\pluck($galleries, 'post_name'), F\pluck($galleries, 'post_title'));
		include(MU_PLUGIN_BASE_DIR.'/templates/form-options.php');
	}

	public static function ajaxContentResponse() {
		if (!isset($_POST['what'])) {
			$success = FALSE;
			$data = 'Invalid request';
		}
		else switch ($_POST['what']) {
		case 'gallery':
			$success = (bool)($out = self::instance()->acfGallery($_POST['who'], FALSE, 'Gallery'));
			$data = $success ? $out : 'Invalid gallery name';
			break;
		case 'page':
			$success = (bool)($out = self::instance()->getPageContent($_POST['who']));
			$data = $success ? $out : 'Invalid page name';
			break;
		default:
			$success = FALSE;
			$data = 'Invalid content type';
		}
		echo json_encode(compact('success','data'));
		wp_die();
	}

	public function svgSrc($name, $dir='icons') {
		$path = get_template_directory()."/img/$dir/$name.svg";
		if (!is_readable($path)) {
			trigger_error("SVG icon '$name' not found or not readable.", E_USER_WARNING);
			return '';
		}
		$svg = preg_replace('/>\s+</', '><', file_get_contents($path));
		$svg = preg_replace('/id=".*?"/', sprintf('id="%s"', "svg-$name"), $svg);
		$svg = substr($svg, strrpos($svg, '?>') + 2);
		return $svg;
	}

	public function svgIcon($title, $name='') {
		if (empty($name)) $name = sanitize_title($title);
		$svg = $this->svgSrc($name);
		$svg = preg_replace('/title=".*?"/', sprintf('title="%s"', $title), $svg);
		return sprintf('<span class="svg-icon" data-icon="%s">%s<span class="svg-fallback">%s</span></span>', $name, $svg, $title);
	}

}
