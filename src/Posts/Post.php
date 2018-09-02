<?php
namespace WordpressLib\Posts;

class Post {

	protected $post;

	public function __construct($id) {
		$post = get_post($id);
		if (!$post) throw new \InvalidArgumentException("Invalid post ID $id.");
		$this->post = $post;
	}

	public function getUrl() {
		return get_permalink($this->post);
	}

	public function getId() {
		return $this->post->ID;
	}

	public function getTitle() {
		return $this->post->post_title;
	}

}
