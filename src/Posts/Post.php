<?php
namespace WordpressLib\Posts;

class Post {

	protected $post;

	protected $autoCreate = FALSE;

	public function __construct($value=NULL, $type='post', $title='', $slug='', $content='') {
		if (!$value) {
			return new \WP_Post(1);
		}

		if (is_numeric($value)) $post = get_post($value);
		if (is_string($value)) $post = get_page_by_path($value, OBJECT, $type);
		if (is_object($value)) $post = $value;
		if (is_array($value)) $post = (object)$value;
		
		if ($post) {
			$this->post = $post;
		}
		else {
			if ($this->autoCreate && !empty($title) && $this->isExistingPost() == FALSE) {
				$id = $this->create($title, $slug, $type, $content);
			}
			else $this->post = new \WP_Post($value);
		}
	}

	public function getPost() {
		return $this->post;
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

	public function isExistingPost() {
		return $this->post && $this->post->ID;
	}

	public function generateFields($title, $slug, $type, $content) {
		$fields = [
			'post_title' => $title,
			'post_status' => 'publish',
			'post_type' => $type,
		];

		if (!empty($slug)) $fields['post_name'] = $slug;
		if (is_string($content)) $fields['post_content'] = $content;

		return $fields;
	}

	public function create($title, $slug='', $type='post', $content='') {
		$id = wp_insert_post(
			$this->generateFields($title, $slug, $type, $content),
			TRUE
		);

		if (is_wp_error($id)) {
			if (WP_DEBUG) {
				var_dump(__FILE__.":".__LINE__." - ".__METHOD__, $id);
			}
		}
		else {
			$this->post = get_post($id);
		}

		return $id;
	}

	public function update($title, $slug, $content=NULL) {
		if ($this->isExistingPost() == FALSE) {
			throw new \BadMethodCallException("Cannot update a non-existing post.");
		}

		$fields = $this->generateFields($title, $slug, $this->post->post_type, $content);
		$fields['ID'] = $this->post->ID;

		$id = wp_insert_post($fields, TRUE);

		if (is_wp_error($id)) {
			if (WP_DEBUG) {
				var_dump(__FILE__.":".__LINE__." - ".__METHOD__, $id);
			}
		}
		else {
			$this->post = get_post($id);
		}

		return $id;
	}

	public function delete() {
		if ($this->isExistingPost() == FALSE) {
			throw new \BadMethodCallException("Cannot delete a non-existing post.");
		}

		$post = wp_delete_post($this->post->ID);
		
		if ($post == NULL) {
			if (WP_DEBUG) {
				var_dump(__FILE__.":".__LINE__." - ".__METHOD__, $id);
			}
		}

		return $post;
	}

}
