<?php

trait TilesTrait {

	public function tiles($info_field_name, $media_field_name) {
		$tiles = new TilesInfo(get_field($info_field_name), get_field($media_field_name));
		include(MU_PLUGIN_BASE_DIR.'/templates/tiles.php');
	}

	public function tileContent($content, $type) {
		$fn = "{$type}Content";
		return $this->$fn($content);
	}

	protected function breakContent() {
		return '';
	}

	protected function imageContent($acfImage) {
		return self::instance()->acfResponsiveImage($acfImage);
	}

	protected function videoContent($acfVideo) {
		return sprintf('<video autoplay loop src="%s"></video>', $acfVideo['url']);
	}

	protected function pageContent($post) {
		return sprintf('<h2>%s</h2><hr><div>%s</div>', get_the_title($post), apply_filters('the_content', $post->post_content));
	}

}
