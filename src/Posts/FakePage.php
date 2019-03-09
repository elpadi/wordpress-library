<?php
namespace WordpressLib\Posts;

abstract class FakePage {

	protected $queryVar = 'fakepage';

	public function __construct($slug, $title) {
		$this->slug = $slug;
		$this->title = $title;
		$this->wasSet = FALSE;
		add_filter('query_vars', [$this, 'addQueryVar']);
		add_filter('template_include', [$this, 'loadPageTemplate']);
		add_action('init', [$this, 'addRewriteRules']);
		if (get_option("fakepage_{$this->queryVar}_needs_flush")) {
			add_action('init', 'flush_rewrite_rules');
		}
		add_filter('the_content', [$this, 'replaceContent']);
		add_filter('document_title_parts', [$this, 'replaceDocumentTitleParts']);
		add_filter('the_title', [$this, 'replaceContentTitle'], 10, 2);
		add_filter('body_class', [$this, 'addBodyClass']);
	}

	public function loadPageTemplate($template) {
		global $wp_query;
		if (array_key_exists($this->queryVar, $wp_query->query_vars) == FALSE) {
			update_option("fakepage_{$this->queryVar}_needs_flush", 1);
		}
		else {
			if ($wp_query->query_vars[$this->queryVar] == $this->slug) {
				return locate_template('page.php');
			}
		}
		return $template;
	}

	public function addRewriteRules($rules) {
		add_rewrite_tag("%$this->queryVar%", '([^&]+)');
		add_rewrite_rule(
			sprintf('%s/?$', $this->slug),
			"index.php?$this->queryVar=$this->slug",
			'top'
		);
	}

	public function addQueryVar($vars) {
		$vars[] = $this->queryVar;
		return $vars;
	}

	public function isBeingRequested() {
		global $wp_query;
		return isset($wp_query->query_vars[$this->queryVar]) && $wp_query->query_vars[$this->queryVar] == $this->slug;
	}

	public function replaceContent($s) {
		return $this->isBeingRequested() ? $this->createContent() : $s;
	}

	public function replaceContentTitle($s, $id) {
		return $this->isBeingRequested() && $id === 1 ? $this->title : $s;
	}

	public function addBodyClass($classes) {
		if ($this->isBeingRequested()) {
			$classes[] = $this->queryVar;
			$classes[] = "$this->queryVar--$this->slug";
		}
		return $classes;
	}

	public function replaceDocumentTitleParts($parts) {
		if ($this->isBeingRequested()) $parts['title'] = $this->title;
		return $parts;
	}

	abstract protected function createContent();

}
