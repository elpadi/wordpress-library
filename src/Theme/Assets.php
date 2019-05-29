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
		if (!is_dir($this->getDir())) {
			throw new \RuntimeException("Asset directory does not exist.");
		}
	}

	public function changePath($assetPath) {
		return new static($this->prefix, $this->baseUri, $this->baseDir, $assetPath);
	}

	public function getUrl() {
		return "$this->baseUri/$this->assetPath";
	}

	public function getDir() {
		return "$this->baseDir/$this->assetPath";
	}

	public function asset($path, $callback, $ext='', $folder='', $deps=[], $enqueue=true) {
		$handle = $this->prefix.'_'.str_replace('/', '-', $path);
		call_user_func(
			$enqueue ? $callback : str_replace('enqueue', 'register', $callback),
			$handle,
			"$this->baseUri/$this->assetPath/$folder/$path.$ext",
			$deps,
			filemtime(realpath("$this->baseDir/$this->assetPath/$folder/$path.$ext"))
		);
		return $handle;
	}

	public function raw($type, $handle, $path, $deps=[], $enqueue=true) {
		$callback = $type == 'js' ? 'wp_enqueue_script' : 'wp_enqueue_style';
		call_user_func(
			$enqueue ? $callback : str_replace('enqueue', 'register', $callback),
			$handle,
			"$this->baseUri/$this->assetPath/$path",
			$deps,
			filemtime(realpath("$this->baseDir/$this->assetPath/$path"))
		);
	}

	public function js($path, $deps=[], $enqueue=true) {
		return $this->asset($path, 'wp_enqueue_script', 'js', 'js', $deps, $enqueue);
	}

	public function css($path, $deps=[], $enqueue=true) {
		return $this->asset($path, 'wp_enqueue_style', 'css', 'css', $deps, $enqueue);
	}

	public function assetDirPathComparator($a, $b) {
		return strlen($a) - strlen($b);
	}

	public function dir($path, $type, $deps=[], $enqueue=true) {
		$base = "$this->baseDir/$this->assetPath/$type";
		$files = glob("$base/$path/*.$type");
		if (count($files) == 0) return [];
		$files = array_map(function($f) use ($type) { return basename($f, ".$type"); }, $files);
		usort($files, [$this, 'assetDirPathComparator']);
		foreach ($files as $file) {
			$handles[] = call_user_func([$this, $type], "$path/" . $file, $deps, $enqueue);
		}
		return $handles;
	}

}
