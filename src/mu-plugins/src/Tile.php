<?php

class Tile {

	public $width;
	public $height;

	public function __construct($type, $size, $content) {
		$dims = explode('x', $size);
		$this->width = $dims[0];
		$this->height = $dims[1];
		$this->type = $type;
		$this->content = apply_filters('tile_content', $content, $type);
	}

}
