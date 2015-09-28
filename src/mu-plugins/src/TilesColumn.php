<?php

class TilesColumn extends ArrayObject {

	public $width;
	public $height;

	public function __construct($width, $height, $types, $sizes, $params=array()) {
		parent::__construct([]);
		$this->width = $width;
		$this->height = $height;
		foreach ($types as $i => $type) {
			$this->append(new Tile($type, $sizes[$i], $params[$i]));
		}
	}

}
