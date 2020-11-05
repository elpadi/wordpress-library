<?php
namespace Tome\Menu;

use Tome\Plugins\Plugin;

class Menu extends Plugin {

	public $pluginSlug = 'menu';
	public $screenTitle = 'Menu';

	public function __construct($modules) {
		parent::__construct();
		$this->modules = $modules;
		add_filter('tome-admin_theme_menu_template_vars', [$this, 'setMenuEmbedSrc']);
		add_action('wp_ajax_menu_location_change', [$this, 'updateMenuLocation']);
	}

	public function updateMenuLocation() {
		$result = $this->modules->updateMetaValue('menu_location', $_POST['value']);
		if ($result === FALSE || is_wp_error($result)) {
			if (\WP_DEBUG) print_r($result);
			$success = FALSE;
			$message = 'Could not change menu location.';
		}
		else {
			$success = TRUE;
			$message = "Menu location changed to $_POST[value].";
		}
		header('Content-type: application/json');
		echo json_encode(compact('success','message'));
		wp_die();
	}

	public function getCurrentMenu() {
		if (!$this->modules->selected) {
			return NULL;
		}
		foreach (wp_get_nav_menus() as $menu) {
			if ($menu->name == $this->modules->selected->name) {
				return $menu;
			}
		}
		return NULL;
	}

	public function getMenuLocation() {
		return $this->modules->getMetaValue('menu_location', 'side');
	}

	protected function createMenu() {
		$id = wp_update_nav_menu_object(0, ['menu-name' => $this->modules->selected->name]);
		if (is_wp_error($id)) {
			throw new \RuntimeException("Could not create a menu for the selected module.");
		}
		return wp_get_nav_menu_object($id);
	}

	public function setMenuEmbedSrc($vars) {
		if (!$this->modules->selected) {
			throw new \BadMethodCallException("A module must be selected.");
		}

		$menu = ($m = $this->getCurrentMenu()) ? $m : $this->createMenu();

		$vars['embed_url'] = admin_url('nav-menus.php').'?action=edit&menu='.$menu->term_id.'&embedded=true';
		$vars['menu_location'] = $this->getMenuLocation();

		return $vars;
	}

	public function addSubMenus($submenus) {
		$submenus[$this->pluginSlug] = $this->screenTitle;
		return $submenus;
	}

}
