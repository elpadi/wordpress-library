<?php
// Make sure we don't expose any info if called directly
if (!function_exists( 'add_action')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Stop Contact Form 7 from printing useless assets
 */
define('WPCF7_LOAD_JS', false);
define('WPCF7_LOAD_CSS', false);

define('MU_PLUGIN_BASE_DIR', __DIR__);
spl_autoload_register(function($class) {
	is_file($path = __DIR__."/src/$class.php") && include($path);
});

add_action('init', array(MU_SITE_CLASS_NAME, 'instance'));
add_action('wp_ajax_nopriv_content', array(MU_SITE_CLASS_NAME, 'ajaxContentResponse'));
add_action('wp_ajax_content', array(MU_SITE_CLASS_NAME, 'ajaxContentResponse'));
