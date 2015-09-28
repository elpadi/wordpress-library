<?php

trait GridTrait {

	protected function getGridItemClasses($colcount, $index, $count) {
		$center = $colcount / 2;
		$col = $index % $colcount;
		$row = floor($index / $colcount);
		$last_row = floor(($count - 1) / $colcount);

		$classes[] = $col <= $center - 1 ? 'left' : ($colcount % 2 !== 0 && $col == floor($center) ? 'center' : 'right');
		if ($col === 0) $classes[] = 'first-col';
		if ($col === $colcount - 1) $classes[] = 'last-col';
		if ($row == 0) $classes[] = 'first-row';
		if ($row == $last_row) $classes[] = 'last-row';

		return array_map(function($s) { return "grid__item--$s"; }, $classes);
	}
	
	/*
	protected function grid_item_class($classes, $colcount, $index=-1, $count=-1, $prefix='grid__item') {
		global $wp_the_query;
		if ($index === -1) {
			$index = $wp_the_query->current_post;
			$count = $wp_the_query->post_count;
		}
		return array_merge($classes, $this->getGridItemClasses($colcount, $index, $count, $prefix));
	}
	 */
	
	protected function navItemToGridImageItem($link, $index, $count, $colcount) {
		$classes = $this->getGridItemClasses($colcount, $index, $count);
		$url = $link->url;
		$image = $this->acfResponsiveImage('intro_image', $link->object_id);
		$title = $link->title;
		return apply_filters('grid_image_item', compact('classes','url','image','title'), $link);
	}

	protected function postToGridImageItem($post, $index, $count, $colcount) {
		$classes = $this->getGridItemClasses($colcount, $index, $count);
		$url = get_the_permalink($post);
		$image = $this->acfResponsiveImage('intro_image', $post->ID);
		$title = get_the_title($post);
		return apply_filters('grid_image_item', compact('classes','url','image','title'), $post);
	}

	public function subsectionsGrid($id='') {
		$subsections_menu = get_field('subsections');
		if (!$subsections_menu) return;
		$container_classes = ['grid--image','grid--subsections'];
		if (!empty($id)) $container_classes[] = "grid--$id";
		$colcount = 3;
		$sections = wp_get_nav_menu_items($subsections_menu);
		$count = count($sections);
		foreach ($sections as $i => $post) $items[] = $this->navItemToGridImageItem($post, $i, $count, $colcount);
		include(MU_PLUGIN_BASE_DIR.'/templates/grid.php');
	}

}
