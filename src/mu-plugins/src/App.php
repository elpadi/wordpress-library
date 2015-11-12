<?php
defined('ABSPATH') || exit;

class App implements SingletonInterface, ImagesInterface, GridInterface, TilesInterface, PaginationInterface, PostQueriesInterface {
	use SingletonTrait;
	use PostsTrait;
	use PostTypesTrait;
	use PaginationTrait;
	use PostQueriesTrait;
	use ImagesTrait;
	use GridTrait;
	use TilesTrait;
	use SiteTrait;

	final protected function __construct() {
		add_filter('grid_item_class', array($this, 'grid_item_class'), 10, 5);
		add_action('wp', array($this, 'theme_init'));
		add_action('admin_init', array($this, 'siteSettings'));
		add_action('widgets_init', array($this, 'initWidgets'));
		if (defined('DISABLE_ADMIN_BAR') && DISABLE_ADMIN_BAR) {
			add_filter('show_admin_bar', '__return_false');
		}
		$this->siteInit();
	}

}
