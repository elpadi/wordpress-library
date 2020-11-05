<?php
namespace WordpressLib\Posts;

abstract class FakePage {

	protected $templateName = 'page';
	protected $queryVar = 'fakepage';

	public function __construct($slug, $title) {
		$this->slug = $slug;
		$this->title = $title;
		$this->wasSet = FALSE;
		add_filter('query_vars', [$this, 'addQueryVar']);
		add_action('init', [$this, 'addRewriteRules']);
		if (get_option("fakepage_{$this->queryVar}_needs_flush")) {
			add_action('init', 'flush_rewrite_rules');
		}
		$this->setContentFilters();
	}

	protected function setContentFilters() {
		add_filter('template_include', [$this, 'replaceTemplate']);
		add_filter('the_content', [$this, 'replaceContent']);
		add_filter('document_title_parts', [$this, 'replaceDocumentTitleParts']);
		add_filter('the_title', [$this, 'replaceContentTitle'], 10, 2);
		add_filter('body_class', [$this, 'addBodyClass']);
	}

	public function replaceTemplate($template) {
		global $wp_query;
		if (array_key_exists($this->queryVar, $wp_query->query_vars) == FALSE) {
			update_option("fakepage_{$this->queryVar}_needs_flush", 1);
		}
		else {
			if ($wp_query->query_vars[$this->queryVar] == $this->slug) {
				return locate_template("$this->templateName.php");
			}
		}
		return $template;
	}

	public function addRewriteRules() {
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

	public function isBeingRequested($query=NULL) {
		global $wp_query;
		if (!$query) $query = $wp_query;
		return isset($query->query_vars[$this->queryVar]) && $query->query_vars[$this->queryVar] == $this->slug;
	}

	public function replaceContent($s) {
		return $this->isBeingRequested() ? $this->createContent() : $s;
	}

	public function replaceContentTitle($s, $id) {
		return $this->isBeingRequested() && $id === 1 ? $this->title : $s;
	}

	protected function getBodyClasses() {
		return [
			$this->queryVar,
			"$this->queryVar--$this->slug",
		];
	}

	public function addBodyClass($classes) {
		return $this->isBeingRequested() ? array_merge($classes, $this->getBodyClasses()) : $classes;
	}

	public function replaceDocumentTitleParts($parts) {
		if ($this->isBeingRequested()) $parts['title'] = $this->title;
		return $parts;
	}

	abstract protected function createContent();

}
