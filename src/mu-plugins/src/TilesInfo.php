<?php

class TilesInfo extends ArrayObject {

	const COLUMNS = 'rowSize,types,colSizes,params';
	const MEDIA_TYPES = 'image,video';

	protected $media;
	protected $totalWidth = 0;

	public function __construct($field, $media) {
		parent::__construct([]);
		$this->media = $media;
		$rowIndex = 0;
		$rows = [];
		foreach ($field['body'] as $i => $tableRow) {
			$column = $this->tilesColumn($this->parseTableRow($tableRow));
			$rows[$rowIndex][] = $column;
			
			$this->totalWidth += $column->width;
			if ($this->totalWidth >= 100) {
				$rowIndex++;
				$this->totalWidth = 0;
			}
		}
		foreach ($rows as $row) $this->append($row);
	}

	protected function parseTableRow($row) {
		$cols = explode(',', self::COLUMNS);
		foreach ($row as $i => $cell) {
			$parsed_row[$cols[$i]] = $cell['c'];
		}
		return $parsed_row;
	}

	protected function tilesColumn($row) {
		$media_types = explode(',', self::MEDIA_TYPES);
		$media =& $this->media;
		$dims = array_map(function($f) { return (float)$f; }, explode('x', $row['rowSize']));
		$types = explode(',', $row['types']);
		$sizes = explode(',', $row['colSizes']);
		$rowParams = explode(',', $row['params']);
		if ($types[0] === 'break') {
			return new TilesColumn(100, 0, $types, ['100x'], [NULL]);
		}
		foreach ($types as $i => $type) {
			$fn = "{$type}Param";
			$params[] = $this->$fn($rowParams);
		}
		return new TilesColumn($dims[0], $dims[1], $types, $sizes, $params);
	}

	protected function videoParam() {
		return $this->mediaParam();
	}

	protected function imageParam() {
		return $this->mediaParam();
	}

	protected function pageParam($params) {
		global $post;
		$name = array_shift($params);
		$path = get_page_uri($post->ID).'/'.$name;
		return get_page_by_path($path);
	}

	protected function mediaParam() {
		return array_shift($this->media);
	}

}
