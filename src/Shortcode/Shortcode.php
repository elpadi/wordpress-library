<?php
namespace WordpressLib\Shortcode;

abstract class Shortcode {

	protected $atts;
	protected $name;

	public function __construct($name, $atts) {
		$this->name = $name;
		$this->atts = $atts;
		add_shortcode($name, [$this, 'handler']);
	}

	public function handler($atts, $content, $tag) {
		ob_start();
		$_atts = shortcode_atts($this->atts, $atts);
		$this->output($_atts, $content, $tag);
		return ob_get_clean();
	}

	abstract protected function output($_atts, $content, $tag);

}
