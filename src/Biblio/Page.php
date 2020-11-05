<?php
namespace Tome\Biblio;

use WordpressLib\Posts\FakePage;

class Page extends FakePage {

	public function __construct($modules) {
		parent::__construct('bibliography', __('Bibliography', 'tome'));
		$this->modules = $modules;
	}

	protected function createContent() {
		if ($this->modules->selected) {
			$refs = new ContentReferences();
			$refs->appendFromPosts($this->modules->getPosts(['page','post']));
			$refs->transformTags();
			$refs->sort();
			if ($refs->count() && ($t = locate_template('template-parts/content/works-cited.php'))) {
				ob_start();
				include($t);
				return ob_get_clean();
			}
			else return '<p>'.__('Did not find any bibliographic entries in the current module.', 'tome').'</p>';
		}
		return '<p>'.__('You must select a module in order to read its bibliography.', 'tome').'</p>';
	}

}
