<?php
namespace MustUsePlugin;
use Functional as F;

abstract class Site {

	protected static $SITE_PREFIX = 'site_';

	public static function prefix($s) {
		return static::$SITE_PREFIX.$s;
	}

	abstract protected function siteInit();
	abstract protected function siteSettings();
	abstract public function themeInit();

	public function isAcfCheckboxChecked($field_name, $post_id=0, $value_string='') {
		$value = get_field($field_name, $post_id);
		if (is_bool($value)) return $value;
		if (is_array($value) && count($value) && (empty($value_string) || $value[0] === $value_string)) return TRUE;
		return FALSE;
	}

	protected function enableAjaxContent() {
		add_action('wp_ajax_nopriv_content', array(MU_SITE_CLASS_NAME, 'ajaxContentResponse'));
		add_action('wp_ajax_content', array(MU_SITE_CLASS_NAME, 'ajaxContentResponse'));
	}

	protected function checkAdminBarStatus() {
		if (defined('DISABLE_ADMIN_BAR') && DISABLE_ADMIN_BAR) {
			add_filter('show_admin_bar', '__return_false');
		}
	}

	public function acfSelectOptions($group_name, $field_name) {
		$settings = get_posts(['name' => "acf_$group_name", 'post_type' => 'acf']);
		if (!count($settings)) return [];
		$field_meta = F\select(F\pluck(get_post_meta($settings[0]->ID), 0), function($val, $key) use ($field_name) {
			return strpos($key, 'field_') === 0
				&& ($data = unserialize($val))
				&& $data['name'] === $field_name;
		});
		if (!count($field_meta)) return [];
		$data = unserialize(reset($field_meta));
		return $data['choices'];
	}

	public function postsDropdownField($post_type, $name, $value) {
		$galleries = get_posts(['post_type' => self::prefix($post_type), 'posts_per_page' => -1]);
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
