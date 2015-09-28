<?php

interface ImagesInterface {

	const RESPONSIVE_WIDTHS = '320,640,1280,1920,2560';

	public static function blankSrc();
	public function getResponsiveWidths();
	public function acfSlideshow($field_name, $post_id=0, $titles_are_links=false, $is_fullscreen=true);

}
