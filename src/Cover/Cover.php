<?php
namespace Tome\Cover;

use Tome\Plugins\Plugin;
use WordpressLib\Posts\HomePage;

/**
 * Creates a page to be used as the home page, e.g. the global module cover.
 *
 * It also creates one post for each cover, where the cover content will be set.
 */
class Cover extends Plugin {

	public $pluginSlug = 'cover';
	public $screenTitle = 'Cover';

	public $pageId = 0;

	public $title = '';
	public $author = '';

	public function __construct() {
		parent::__construct();
		$this->postType = new PostType();
		if (isset($_GET['embedded'])) add_filter('enter_title_here', [$this, 'updateTitlePlaceholder']);
	}

	public function init() {
		$this->postType->register();
		$this->globalCoverPage = new HomePage('Cover', strtoupper(__('Do not update this page, please use the Tome cover settings.', 'tome')));
	}

	public function updateTitlePlaceholder($s) {
		global $post;

		$t = isset($_GET['post_type']) ? $_GET['post_type'] : ($post ? $post->post_type : '');
		$_t = isset($this->postType) ? $this->postType->slug : '';

		if ($t) return ($t == $_t ? $this->screenTitle : ucfirst($t)).' title';
		return $s;
	}

	public function addSubMenus($submenus) {
		$submenus[$this->pluginSlug] = $this->screenTitle;
		return $submenus;
	}

}
