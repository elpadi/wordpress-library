<?php

interface ImagesInterface {

	const RESPONSIVE_WIDTHS = '320,640,1280,1920,2560';

	public static function blankSrc();
	public function getResponsiveWidths();
	public function acfSlideshow($field_name, $post_id=0, $classes=array());
	public function acfGallery($name, $print=true, $gallery_title='');
	public function acfResponsiveImage($field_name_or_field, $post_id=0, $classes=array());
	public function responsiveFeaturedImage($post_id=0, $classes=array());
	public function responsiveAttachedImage($thumb_data, $classes=array());

}
