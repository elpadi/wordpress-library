<?php

trait PaginationTrait {

	public function pagination($total=-1, $spread=-1) {
		global $wp_query;
		$big = 999999999; // need an unlikely integer
		$current = max( 1, get_query_var('paged') );
		if ($total < 1) $total = $wp_query->max_num_pages;
		if ($spread < 1) $spread = self::DEFAULT_PAGINATION_SPREAD;
		$spread_left = min($current - 1, $spread * 2);
		$spread_right = min($current + $spread * 2, $total) - $current;
		if ($spread_left + $spread_right > 4) {
			if ($spread_left >= 2 && $spread_right >= 2) $spread_left = $spread_right = 2;
			elseif ($spread_left < 2) $spread_right = 4 - $spread_left;
			else $spread_left = 4 - $spread_right;
		}
		$pages = range($current - $spread_left, $current + $spread_right);
		$links = array_map(function($n) use ($current) {
			return '<li>'.($n == $current ? "<span>$current</span>" : sprintf('<a href="%s">%s</a>', get_pagenum_link($n), $n)).'</li>';
		}, $pages);
		$out = '<nav class="pagination">';
		$out .= sprintf('<a class="edge edge--left" href="%s">First</a>', get_pagenum_link(1));
		$out .= sprintf('<ul class="nav-menu">%s</ul>', implode('', $links));
		$out .= sprintf('<a class="edge edge--right" href="%s">Last</a>', get_pagenum_link($total));
		$out = '</nav>';
		return $out;
	}

}
