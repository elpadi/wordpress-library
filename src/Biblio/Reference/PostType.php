<?php
namespace Tome\Biblio\Reference;

use WordpressLib\Posts\CustomType;

class PostType extends CustomType {

	protected static $supports = ['title','editor'];

	public function __construct() {
		parent::__construct('tome-reference', 'Reference');
	}

	protected function createSettings() {
		$settings = parent::createSettings();
		$settings['template'] = [
			['tome2/reference-data'],
			['tome2/reference-output'],
		];
		$settings['template_lock'] = 'all';
		return $settings;
	}

	public function updateRestFields($response, $post, $request) {
		foreach (parse_blocks($post->post_content) as $b) {
			if ($b['blockName'] == 'tome2/reference-output') {
				foreach ($b['attrs'] as $k => $v) {
					if (!empty($v)) {
						$response->data['fullCitation'] = strip_tags($b['attrs']['mla']);
						return $response;
					}
				}
			}
		}
		$response->data['fullCitation'] = $post->post_title;
		return $response;
	}

}
