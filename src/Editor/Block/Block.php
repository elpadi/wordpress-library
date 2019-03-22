<?php
namespace WordpressLib\Editor\Block;

class Block {

	protected $jsDeps = [];

	public function __construct($pluginSlug, $blockSlug, $frontAssets, $editorAssets) {
		$this->pluginSlug = $pluginSlug;
		$this->blockSlug = $blockSlug;
		$this->frontAssets = $frontAssets;
		$this->editorAssets = $editorAssets;
		add_action('init', [$this, 'register']);
	}

	protected function createSettings() {
		return [];
	}

	public function registerShortcode() {
		add_filter('the_content', [$this, 'contentToShortcode'], -10);
	}

	public function register() {
		register_block_type("$this->pluginSlug/$this->blockSlug", $this->createSettings());
		if (method_exists($this, 'contentToShortcode') && !is_admin()) $this->registerShortcode();
	}

}
