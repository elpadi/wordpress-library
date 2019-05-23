<?php
namespace WordpressLib\Posts;

class Post {

	protected $post;
	protected $terms;

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

	public function updateField($name, $value) {
		if (isset($this->post->$name)) $this->post->$name = $value;
		else throw new \InvalidArgumentException("Invalid post field $name.");
	}

	public function updateFields($updates) {
		foreach ($updates as $key => $val) $this->updateField($key, $val);
	}

	public function updateTerms($taxonomy, $terms) {
		$this->terms[$taxonomy] = $terms;
	}

	public function save() {
		$data = (array)$this->post;
		$data['tax_input'] = $this->terms;

		$id = wp_insert_post($data);
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

	public function create($title=NULL, $slug=NULL, $type=NULL, $content=NULL) {
		if ($title !== NULL) {
			throw new \BadMethodCallException("Arguments to this method are deprecated.");
		}
		if ($slug !== NULL) {
			throw new \BadMethodCallException("Arguments to this method are deprecated.");
		}
		if ($type !== NULL) {
			throw new \BadMethodCallException("Arguments to this method are deprecated.");
		}
		if ($content !== NULL) {
			throw new \BadMethodCallException("Arguments to this method are deprecated.");
		}
		return $this->save();
	}

	public function update($title=NULL, $slug=NULL, $content=NULL) {
		if ($title !== NULL) {
			throw new \BadMethodCallException("Arguments to this method are deprecated.");
		}
		if ($slug !== NULL) {
			throw new \BadMethodCallException("Arguments to this method are deprecated.");
		}
		if ($content !== NULL) {
			throw new \BadMethodCallException("Arguments to this method are deprecated.");
		}
		if ($this->isExistingPost() == FALSE) {
			throw new \BadMethodCallException("Cannot update a non-existing post.");
		}
		return $this->save();
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
