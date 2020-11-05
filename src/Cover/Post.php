<?php
namespace Tome\Cover;

use WordpressLib\Posts\Post as BasePost;


class Post extends BasePost {

	protected static function addSlugSuffix($slug) {
		return "$slug-cover";
	}

	protected static function addTitleSuffix($title) {
		return "$title Cover";
	}

	protected $autoCreate = TRUE;

	public function __construct($moduleTitle, $moduleSlug, $typeSlug) {
		parent::__construct(static::addSlugSuffix($moduleSlug), $typeSlug, static::addTitleSuffix($moduleTitle), static::addSlugSuffix($moduleSlug));
		$this->moduleTitle = $moduleTitle;
		$this->moduleSlug = $moduleSlug;
		$this->typeSlug = $typeSlug;
	}

	public function update($moduleTitle, $moduleSlug, $content='') {
		return parent::update(static::addTitleSuffix($moduleTitle), static::addSlugSuffix($moduleSlug), $content);
	}

}
