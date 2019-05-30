<?php
namespace WordpressLib\Theme;

use WordpressLib\Posts\CustomTaxonomy;

class Admin {

	public function __construct($slug, $title, $pluginDir, $templateVars=[]) {
		$this->slug = $slug;
		$this->title = $title;
		$this->pluginDir = $pluginDir;
		$this->templateVars = $templateVars;
		add_filter('admin_body_class', [$this, 'bodyClass']);
		add_action('init', [$this, 'init'], 20);
		add_action('admin_init', [$this, 'adminInit'], 20);
	}

	public function init() {
	}

	public function adminInit() {
	}

	public function isEmbedded() {
		return isset($_GET['embedded']);
	}

	public function isThemeScreen() {
		global $current_screen;
		return strpos($current_screen->base, $this->slug) !== FALSE || $this->isEmbedded();
	}

	public function isEmbedScreen() {
		global $current_screen;
		return (
			preg_match("/$this->slug-(.*)-settings$/", $current_screen->id, $matches)
			&& in_array($matches[1], ['page','gallery','users'])
		);
	}

	protected function getBodyClass() {
		return $this->slug;
	}

	public function bodyClass($classes) {
		$c = [];
		if ($this->isThemeScreen()) {
			$c[] = $this->getBodyClass();
			if ($this->isEmbedScreen()) $c[] = "$this->slug--embed";
		}
		if ($this->isEmbedded()) $c[] = "$this->slug--embedded";
		return empty($c) ? $classes : $classes.' '.implode(' ', $c);
	}

	protected function getSubMenus() {
		return [];
	}

	public function getScreenPostTypeMap() {
		return [];
	}

	public function getCapabilityFromScreen() {
		return $this->optionsCapability;
	}

	public function getPostTypeFromScreen() {
		$map = $this->getScreenPostTypeMap();
		return get_post_type_object(isset($map[$this->screenSlug]) ? $map[$this->screenSlug] : $this->screenSlug);
	}

	public function getScreenFromPostType($post_type) {
		$name = is_object($post_type) ? $post_type->name : "$post_type";
		return ($screen = array_search($name, $this->getScreenPostTypeMap())) !== FALSE ? $screen : $name;
	}

	public function getScreenTemplateMap() {
		$map = [];
		foreach (['page','post'] as $s) $map[$s] = 'listing';
		return $map;
	}

	public function getScreenTemplate() {
		$map = $this->getScreenTemplateMap();
		if (isset($map[$this->screenSlug])) {
			return $map[$this->screenSlug];
		}
		throw new \BadMethodCallException("Missing template for the section '$this->screenSlug'.");
	}

	public function addOptions($capability, $options=[]) {
		$this->optionsCapability = $capability;
		add_action('admin_menu', function() use ($options, $capability) {
			$handle = "$this->slug-settings";
			add_menu_page("$this->title Settings", $this->title, $capability, $handle, [$this, 'optionsHTML']);
			foreach($this->getSubMenus() as $submenu) {
				extract($submenu);
				add_submenu_page($handle, "$this->title $title", $title, $capability, "$this->slug-$slug-settings", [$this, 'optionsHTML']);
			}
		});
	}

	public function optionsHTML() {
		global $current_screen;
		if (strpos($current_screen->id, "$this->slug-settings") !== FALSE) {
			$this->screenSlug = 'page';
		}
		elseif (preg_match("/$this->slug-(.*)-settings$/", $current_screen->id, $matches)) {
			$this->screenSlug = $matches[1];
		}
		else throw new \InvalidArgumentException("Admin screen '$current_screen->id' is invalid.");
		if (!current_user_can($this->getCapabilityFromScreen())) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		$this->template($this->getScreenTemplate(), TRUE);
	}

	public function template($templateName, $isGlobal=FALSE) {
		global $current_user, $post;
		$adminTheme = $this;

		if ($templateName == 'listing' && isset($_GET['id'])) $templateName = 'single';

		$this->templateVars += get_defined_vars();

		if (is_admin()) {
			$this->templateVars['screenSlug'] = $this->screenSlug;
			$this->templateVars['embed_url'] = admin_url("$this->screenSlug.php").'?embedded=true';
			$this->templateVars['post_type'] = $this->getPostTypeFromScreen();
			$this->templateVars['screenTitle'] = $this->templateVars['post_type'] ? $this->templateVars['post_type']->label : __(ucwords(str_replace('-', ' ', $this->screenSlug)), 'tome');
			$this->templateVars['p'] = isset($_GET['id']) && intval($_GET['id']) ? get_post($_GET['id']) : new \WP_Post(new \stdClass);
			$this->templateVars['_tpl'] = function($dir, $vars=[]) {
				return function() use ($dir, $vars) {
					extract(array_merge($this->templateVars, $vars));
					foreach (func_get_args() as $arg) {
						if (is_string($arg)) $names[] = $arg;
						if (is_array($arg)) extract($arg);
					}
					foreach ($names as $name) include("$dir/$name.php");
				};
			};
			$this->templateVars['icon'] = function($name, $print=TRUE) {
				$html = '';
				if ($h = fopen($this->pluginDir."/assets/img/$name.svg", 'r')) {
					while ($l = fgets($h)) {
						if (strpos($l, '<?') !== FALSE) continue; // skip doctype
						$html .= $l;
					}
					fclose($h);
				}
				if ($print) echo $html;
				return $html;
			};
			$this->templateVars['languages'] = apply_filters('wpml_active_languages', []);
		}

		extract(apply_filters("{$this->slug}_theme_{$this->screenSlug}_template_vars", $this->templateVars));

		if ($isGlobal) {
			echo '<style>html { padding-top: 0 !important; }</style>';
			include($this->pluginDir."/templates/global/before-content.php"); 
		}

		include($this->pluginDir."/templates/content/$templateName.php"); 

		if ($isGlobal) {
			include($this->pluginDir."/templates/global/after-content.php"); 
		}
	}

	public function registerAssets($fn) {
		add_action('admin_enqueue_scripts', function() use ($fn) {
			if ($this->isThemeScreen()) call_user_func($fn);
		});
	}

	public function registerLoginAssets($fn) {
		add_action('login_enqueue_scripts', $fn);
	}

	public function registerBlockAssets($fn) {
		add_action('enqueue_block_editor_assets', $fn);
	}

}
