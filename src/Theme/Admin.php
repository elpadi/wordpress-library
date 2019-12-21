<?php
namespace WordpressLib\Theme;

use WordpressLib\Posts\CustomTaxonomy;

class Admin {

	const DASHBOARD_CAPABILITY = 'publish_posts';

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
		$vars = apply_filters("{$this->slug}_append_template_vars", []);
		$this->templateVars = array_merge($this->templateVars, $vars);
	}

	public function isEditor() {
		foreach (['post','post-new'] as $s)
			if (strpos($_SERVER['REQUEST_URI'], "wp-admin/$s.php") !== FALSE) return TRUE;
		return FALSE;
	}

	public function isTomeAdminScreen() {
		if (strpos($_SERVER['REQUEST_URI'], 'wp-admin/admin.php?page=tome-admin-settings')) return TRUE;
		return preg_match('/wp-admin\/admin\.php\?page=tome-admin(-[a-z]+)-settings/', $_SERVER['REQUEST_URI']);
	}

	public function isThemeScreen() {
		return $this->isTomeAdminScreen() || $this->isEditor();
	}

	protected function getBodyClass() {
		return $this->slug;
	}

	public function bodyClass($classes) {
		$c = [];
		if ($this->isThemeScreen()) {
			$c[] = $this->getBodyClass();
		}
		return empty($c) ? $classes : $classes.' '.implode(' ', $c);
	}

	protected function getSubMenus() {
		return [];
	}

	public function getScreenPostTypeMap() {
		return ['blog' => 'post'];
	}

	public function getCapabilityFromScreen() {
		/*
		foreach($this->getSubMenus() as $submenu) {
			if ($submenu['slug'] == $this->screenSlug) return $submenu['capability'];
		}
		 */
		return self::DASHBOARD_CAPABILITY;
	}

	public function getPostTypeFromScreen() {
		$map = $this->getScreenPostTypeMap();
		return get_post_type_object(isset($map[$this->screenSlug]) ? $map[$this->screenSlug] : $this->screenSlug);
	}

	public function getScreenFromPostType($post_type) {
		$name = is_object($post_type) ? $post_type->name : "$post_type";
		return ($screen = array_search($name, $this->getScreenPostTypeMap())) !== FALSE ? $screen : $name;
	}

	public function addOptions() {
		add_action('admin_menu', function() {
			$handle = "$this->slug-settings";
			add_menu_page("$this->title Settings", $this->title, self::DASHBOARD_CAPABILITY, $handle, [$this, 'optionsHTML']);
			foreach($this->getSubMenus() as $submenu) {
				extract($submenu);
				add_submenu_page($handle, "$this->title $title", $title, self::DASHBOARD_CAPABILITY/*$capability*/, "$this->slug-$slug-settings", [$this, 'optionsHTML']);
			}
		});
	}

	public function optionsHTML() {
		global $current_screen;
		if (strpos($current_screen->id, "$this->slug-settings") !== FALSE) {
			$this->screenSlug = 'dashboard';
		}
		elseif (preg_match("/$this->slug-(.*)-settings$/", $current_screen->id, $matches)) {
			$this->screenSlug = $matches[1];
		}
		else throw new \InvalidArgumentException("Admin screen '$current_screen->id' is invalid.");
		if (!current_user_can(self::DASHBOARD_CAPABILITY/*$this->getCapabilityFromScreen()*/)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		$this->template($this->screenSlug, TRUE);
	}

	public function icon($name, $print=TRUE) {
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
	}

	public function getTemplateLoader($dir, $vars=[]) {
		return function() use ($dir, $vars) {
			extract(array_merge($this->templateVars, $vars));
			foreach (func_get_args() as $arg) {
				if (is_string($arg)) $_filenames[] = $arg;
				if (is_array($arg)) extract($arg);
			}
			foreach ($_filenames as $_filename) include("$dir/$_filename.php");
		};
	}

	public function template($templateName, $isGlobal=FALSE, $isPartial=FALSE, $vars=[]) {
		global $current_user, $post;
		$adminTheme = $this;

		$this->templateVars += get_defined_vars();

		if (is_admin()) {
			if (!$isPartial) {
				$this->templateVars['screenSlug'] = $this->screenSlug;
				$this->templateVars['embed_url'] = admin_url("$this->screenSlug.php").'?embedded=true';
				$this->templateVars['post_type'] = $this->getPostTypeFromScreen();
				$this->templateVars['screenTitle'] = $this->templateVars['post_type'] ? $this->templateVars['post_type']->label : __(ucwords(str_replace('-', ' ', $this->screenSlug)), 'tome');
				$this->templateVars['p'] = isset($_GET['id']) && intval($_GET['id']) ? get_post($_GET['id']) : new \WP_Post(new \stdClass);
			}
			$this->templateVars['_tpl'] = function($dir, $vars=[]) { return $this->getTemplateLoader($dir, $vars); };
			$this->templateVars['tpl'] = $this->getTemplateLoader($this->pluginDir."/templates");
			$this->templateVars['icon'] = function($name, $print=TRUE) {
				return $this->icon($name, $print);
			};
			$this->templateVars['dashicon'] = function($name) {
				return sprintf('<i class="dashicons dashicons-%s"></i>', $name);
			};
			$this->templateVars['activeLanguage'] = apply_filters('wpml_current_language', 'en');
		}

		if ($isPartial) {
			extract(array_merge($this->templateVars, $vars));
		}
		else {
			extract(apply_filters("{$this->slug}_theme_{$this->screenSlug}_template_vars", array_merge($this->templateVars, $vars)));
		}

		if ($isGlobal) {
			echo '<style>html { padding-top: 0 !important; }</style>';
			include($this->pluginDir."/templates/global/before-content.php"); 
		}

		$path = $this->pluginDir.'/templates/'.($isPartial ? 'partial' : 'content').'/'.($isGlobal ? $this->screenSlug : $templateName).'.php';
		if (!is_readable($path)) {
			throw new \InvalidArgumentException("Could not find the template at $templateName.");
		}
		include($path); 

		if ($isGlobal) {
			include($this->pluginDir."/templates/global/after-content.php"); 
		}
	}

	public function partialTemplateString($templateName, $vars=[]) {
		ob_start();
		$this->template($templateName, FALSE, TRUE, $vars);
		return ob_get_clean();
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
