<?php

trait PostsTrait {

	public static function normalizeShortcodeContent($content) {
		// remove starting empty p, ending emtpy p, or break
		if (strpos($content, '</p>') === 0) $content = substr($content, 4);
		if (strpos($content, '<br>') === 0) $content = substr($content, 4);
		if (substr($content, -3) === '<p>') $content = substr($content, 0, strlen($content) - 3);
		$content = trim($content);
		return $content;
	}

	public function getPageContent($name) {
		global $post;
		$post = get_page_by_path($name);
		if (!$post) return '';
		setup_postdata($post);
		ob_start();
		get_template_part('content','page');
		return ob_get_clean();
	}

	public function getChildPages($id=0, $args=array()) {
		if (!$id) $id = get_the_ID();
		return get_posts(array_merge(['post_type' => 'page', 'post_parent' => $id, 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'], $args));
	}

}
