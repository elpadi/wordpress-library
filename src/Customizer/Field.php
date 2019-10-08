<?php
namespace WordpressLib\Customizer;

use Functional as F;

class Field {

	public function __construct($sectionSlug, $type, $slug, $title, $optionType) {
		$this->type = $type;
		$this->slug = $slug;
		$this->title = $title;
		$this->sectionSlug = $sectionSlug;
		$this->optionType = $optionType;
		$this->settingSlug = $this->sectionSlug.'_'.$this->slug;
	}

	public function getValue() {
		return Control::getValue($this->settingSlug, $this->type, $this->optionType);
	}

	public function register($wp_customize) {
		$wp_customize->add_setting($this->settingSlug, ['type' => $this->optionType]);
		new Control($wp_customize, $this->sectionSlug, $this->settingSlug, $this->title, $this->type);
	}

}
