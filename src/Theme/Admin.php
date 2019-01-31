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
	}

	public function isAdminScreen() {
		global $current_screen;
		return strpos($current_screen->base, 'tome-admin') !== FALSE || isset($_GET['embedded']);
	}

	public function bodyClass($classes) {
		if ($this->isAdminScreen()) $classes .= ' tome2-admin ';
		return $classes;
	}

	public function addOptions($capability, $options) {
		$this->optionsCapability = $capability;
		add_action('admin_menu', function() use ($options, $capability) {
			$handle = "$this->slug-settings";
			add_menu_page("$this->title Settings", $this->title, $capability, $handle, [$this, 'optionsHTML']);
			foreach($options as $slug => $title)
				add_submenu_page($handle, "$this->title $title", $title, $capability, "$this->slug-$slug-settings", [$this, 'optionsHTML']);
		});
	}

	public function optionsHTML() {
		global $current_screen;
		if (!current_user_can($this->optionsCapability)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		if (strpos($current_screen->id, "$this->slug-settings") !== FALSE) {
			$template = 'home';
		}
		elseif (preg_match("/$this->slug-(.*)-settings$/", $current_screen->id, $matches)) {
			$template = $matches[1];
		}
		else throw new \InvalidArgumentException("Admin screen '$current_screen->id' is invalid.");
		$this->template($template, TRUE);
	}

	public function template($templateName, $isGlobal=FALSE) {
		global $current_user, $post;
		extract($this->templateVars);
		$p = isset($_GET['post_id']) ? get_post($_GET['post_id']) : new \WP_Post(new \stdClass);
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
		add_action('admin_enqueue_scripts', $fn);
		add_action('login_enqueue_scripts', $fn);
	}

	public function registerBlockAssets($fn) {
		add_action('enqueue_block_editor_assets', $fn);
	}

}
