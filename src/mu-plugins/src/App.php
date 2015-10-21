<?php
defined('ABSPATH') || exit;

abstract class App extends Site implements SingletonInterface, ImagesInterface, GridInterface, TilesInterface, PaginationInterface, PostQueriesInterface {
	use SingletonTrait;
	use PostsTrait;
	use PostTypesTrait;
	use PaginationTrait;
	use PostQueriesTrait;
	use ImagesTrait;
	use GridTrait;
	use TilesTrait;

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
