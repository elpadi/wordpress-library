<?php
namespace WordpressLib\Customizer;

class Control {

	public static function getValue($slug, $type, $optionType) {
		$value = $optionType == 'theme_mod' ? get_theme_mod($slug) : get_option($slug);

		if ($value) switch ($type) {
			case 'page': return get_post($value);
			case 'image': return wp_get_attachment_image($value, 'full');
		}

		return $value;
	}

	public function __construct($wp_customize, $sectionSlug, $slug, $title, $type) {
		$this->sectionSlug = $sectionSlug;
		$this->slug = $slug;
		$this->title = $title;
		$this->type = $type;

		$fn = is_callable([$this, $type]) ? $type : 'add';
		$this->$fn($wp_customize);
	}

	protected function add($wp_customize) {
		$wp_customize->add_control($this->slug, [
			'type' => isset($this->fieldType) ? $this->fieldType : $this->type,
			'section' => $this->sectionSlug,
			'label' => $this->title,
		]);
	}

	protected function image($wp_customize) {
		$wp_customize->add_control(new \WP_Customize_Media_Control($wp_customize, $this->slug, [
			'mime_type' => 'image',
			'section' => $this->sectionSlug,
			'label' => $this->title,
		]));
	}

	protected function text($wp_customize) {
		$this->add($wp_customize);
	}

	protected function page($wp_customize) {
		$this->fieldType = 'dropdown-pages';
		$this->add($wp_customize);
	}

}
