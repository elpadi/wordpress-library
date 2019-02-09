<?php
namespace WordpressLib\Widgets;

class Sidebar {

	public function __construct($slug, $title='', $description='') {
		if (empty($title)) $title = ucwords(implode(' ', explode('-', $slug)));
		if (empty($description)) $description = "Add widgets here to appear in $title";
		$this->slug = $slug;
		$this->title = $title;
		$this->description = $description;
		add_action('widgets_init', [$this, 'register']);
	}

	public function register() {
		register_sidebar([
			'name'          => $this->title,
			'id'            => $this->slug,
			'description'   => $this->description,
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		]);
	}

}
