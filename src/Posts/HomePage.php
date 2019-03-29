<?php
namespace WordpressLib\Posts;

class HomePage extends Post {

	protected $autoCreate = TRUE;

	public function __construct($title, $content) {
		parent::__construct(intval(get_option('page_on_front')), 'page', $title, '', $content);
		$this->title = $title;
	}

}
