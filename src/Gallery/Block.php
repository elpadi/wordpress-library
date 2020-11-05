<?php
namespace Tome\Gallery;

use WordpressLib\Editor\Block\Block as BaseBlock;

class Block extends BaseBlock {

	public function contentToShortcode($content) {
		$mid = strpos($content, 'Tome Gallery "');
		if ($mid) {
			$start = strrpos(substr($content, 0, $mid), '<h3');
			$end = strpos($content, '/h3>', $mid) + 4;
			$chunk = substr($content, $start, $end - $start);
			preg_match('/>([0-9]+)</', $chunk, $matches);
			$shortcode = sprintf('[%s-%s id="%d" /]', $this->pluginSlug, $this->blockSlug, $matches[1]);
			return substr($content, 0, $start).$shortcode.substr($content, $end);
		}
		return $content;
	}

	public function registerShortcode() {
		parent::registerShortcode();
		new Shortcode($this->pluginSlug.'-'.$this->blockSlug, ['id' => 0]);
	}

	protected function createSettings() {
		$settings = parent::createSettings();
		$settings['attributes'] = [
			'gallery_id' => ['type' => 'string'],
		];
		return $settings;
	}

}
