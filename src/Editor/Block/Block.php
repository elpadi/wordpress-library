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
		$ed_js_handle = $this->editorAssets->js("block-editor/blocks/$this->blockSlug", array_merge(['wp-blocks','wp-element','wp-editor'], $this->jsDeps), FALSE);
		$ed_css_handle = $this->editorAssets->css("block-editor/blocks/$this->blockSlug", [], FALSE);
		$fr_css_handle = $this->frontAssets->css("block-editor/blocks/$this->blockSlug", [], FALSE);
		return [
			'editor_script' => $ed_js_handle,
			'editor_style' => $ed_css_handle,
			'style' => $fr_css_handle,
		];
	}

	public function registerShortcode() {
		add_filter('the_content', [$this, 'contentToShortcode'], -10);
	}

	public function register() {
		register_block_type("$this->pluginSlug/$this->blockSlug", $this->createSettings());
		if (method_exists($this, 'contentToShortcode') && !is_admin()) $this->registerShortcode();
	}

}
