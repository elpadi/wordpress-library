<?php
namespace WordpressLib\Theme;

class Assets {

	protected $baseUri;
	protected $baseDir;
	protected $assetPath;
	protected $prefix;

	public function __construct($baseUri, $baseDir, $assetPath) {
		$this->baseUri = $baseUri;
		$this->baseDir = $baseDir;
		$this->assetPath = $assetPath;
		$this->prefix = basename(get_stylesheet_directory());
	}

	public function asset($path, $callback, $ext='', $folder='', $deps=[], $version='') {
		call_user_func(
			$callback,
			$this->prefix.'_'.basename($path),
			"$this->baseUri/$this->assetPath/$folder/$path.$ext",
			$deps,
			empty($version) ? filemtime("$this->baseDir/$this->assetPath/$folder/$path.$ext") : $version
		);
	}

	public function js($path, $deps=[], $version='') {
		$this->asset($path, 'wp_enqueue_script', 'js', 'js');
	}

	public function css($path, $deps=[], $version='') {
		$this->asset($path, 'wp_enqueue_style', 'css', 'css');
	}

}
