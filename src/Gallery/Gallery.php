<?php
namespace Tome\Gallery;

use Tome\Plugins\Plugin;

class Gallery extends Plugin {

	public $pluginSlug = 'galleries';
	public $screenTitle = 'Galleries';

	public function __construct($frontAssets, $editorAssets) {
		parent::__construct();
		$this->postType = new PostType();
		$this->block = new Block('tome2', 'gallery', $frontAssets, $editorAssets);
	}

	public function init() {
		$this->postType->register();
	}

	public function getPosts() {
		return $this->postType->getPosts();
	}

	public function addSubMenus($submenus) {
		$submenus['galleries'] = 'Galleries';
		$submenus['gallery'] = 'Gallery';
		return $submenus;
	}

}
