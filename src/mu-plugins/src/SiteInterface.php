<?php

interface SiteInterface {
	
	public static function prefix($s);
	public function siteSettings();
	public function theme_init();

}
