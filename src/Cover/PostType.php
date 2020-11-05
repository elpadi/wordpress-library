<?php
namespace Tome\Cover;

use WordpressLib\Posts\CustomType;

class PostType extends CustomType {

	protected static $supports = ['title','editor'];

	public function __construct() {
		parent::__construct('tome-cover', 'Cover');
	}

	protected function createSettings() {
		$settings = parent::createSettings();
		$settings['template'] = [
			['tome2/byline'],
			['core/paragraph', [
				'placeholder' => 'Book description...',
			]],
			['tome2/media', [
				'placeholder' => 'Cover Background',
			], [
				['core/image'],
				['core/gallery'],
				['core/video'],
				['core/embed'],
			]],
		];
		$settings['template_lock'] = 'all';
		return $settings;
	}

}
