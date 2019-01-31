<?php
namespace WordpressLib\Theme;

class Assets {

	protected $baseUri;
	protected $baseDir;
	protected $assetPath;
	protected $prefix;

	public function __construct($prefix, $baseUri, $baseDir, $assetPath) {
		$this->prefix = $prefix;
		$this->baseUri = $baseUri;
		$this->baseDir = $baseDir;
		$this->assetPath = $assetPath;
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

	public function raw($type, $handle, $path, $deps=[], $version='') {
		call_user_func(
			$type == 'js' ? 'wp_enqueue_script' : 'wp_enqueue_style',
			$handle,
			"$this->baseUri/$this->assetPath/$path",
			$deps,
			empty($version) ? filemtime("$this->baseDir/$this->assetPath/$path") : $version
		);
	}

	public function js($path, $deps=[], $version='') {
		$this->asset($path, 'wp_enqueue_script', 'js', 'js', $deps, $version);
	}

	public function css($path, $deps=[], $version='') {
		$this->asset($path, 'wp_enqueue_style', 'css', 'css', $deps, $version);
	}

	public function dir($path, $type, $deps=[]) {
		$base = "$this->baseDir/$this->assetPath/$type";
		foreach (glob("$base/$path/*.$type") as $file) {
			call_user_func([$this, $type], "$path/" . basename($file, ".$type"), $deps);
		}
	}

}
