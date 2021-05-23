<?php

namespace WordpressLib\Customizer;

use Functional as F;

class Field
{

    public function __construct($sectionSlug, $type, $slug, $title, $optionType, $attrs, $defaultValue = '')
    {
        $this->type = $type;
        $this->slug = $slug;
        $this->title = $title;
        $this->sectionSlug = $sectionSlug;
        $this->optionType = $optionType;
        $this->attrs = $attrs;
        $this->defaultValue = $defaultValue;
        $this->settingSlug = $this->sectionSlug . '_' . $this->slug;
    }

    public function getValue()
    {
        return Control::getValue($this->settingSlug, $this->type, $this->optionType);
    }

    public function register($wp_customize)
    {
        $wp_customize->add_setting($this->settingSlug, ['type' => $this->optionType, 'default' => $this->defaultValue]);
        new Control($wp_customize, $this->sectionSlug, $this->settingSlug, $this->title, $this->type, $this->attrs);
    }
}
