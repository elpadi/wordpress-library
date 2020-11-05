<?php
namespace Tome\Gallery;

use WordpressLib\Posts\CustomType;

class PostType extends CustomType {

	public function __construct() {
		parent::__construct('tome-gallery', 'Gallery', 'Galleries');
	}

	protected function createLabels() {
		$labels = parent::createLabels();
		$labels['featured_image'] = 'Gallery Cover';
		return $labels;
	}

	protected function createSettings() {
		$settings = parent::createSettings();
		$settings['template'] = [
			['core/gallery'],
		];
		$settings['template_lock'] = 'all';
		return $settings;
	}

	public function updateRestFields($response, $post, $request) {
		if (!isset($response->data['title']['raw'])) {
			$response->data['title']['raw'] = $post->post_title;
		}
		if ($response->data['featured_media']) {
			$t = wp_get_attachment_image_src($response->data['featured_media'])[0];
		}
		else {
			if (
				($i = strpos($response->data['content']['rendered'], ' 300w'))
				&& ($j = strrpos(substr($response->data['content']['rendered'], 0, $i), 'http'))
			) $t = substr($response->data['content']['rendered'], $j, $i - $j);
		}
		$response->data['cover'] = [
			'thumb' => isset($t) ? $t : '',
		];
		return $response;
	}

}
