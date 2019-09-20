<?php
namespace WordpressLib\Customizer;

use Functional as F;

class Section {

	public function __construct($slug, $title) {
		$this->slug = $slug;
		$this->title = $title;
		$this->controls = [];
		$this->settings = [];
		$this->repeaters = [];
		add_action('customize_register', [$this, 'register']);
	}

	protected function control($wp_customize, $slug, $title, $type) {
		$wp_customize->add_control($slug, [
			'type' => $type,
			'section' => $this->slug,
			'label' => $title,
		]);
	}

	protected function image($wp_customize, $slug, $title) {
		$wp_customize->add_control(new \WP_Customize_Media_Control($wp_customize, $slug, [
			'mime_type' => 'image',
			'section' => $this->slug,
			'label' => $title,
		]));
	}

	protected function text($wp_customize, $slug, $title) {
		$this->control($wp_customize, $slug, $title, 'text');
	}

	protected function page($wp_customize, $slug, $title) {
		$this->control($wp_customize, $slug, $title, 'dropdown-pages');
	}

	protected function registerRepeaters($wp_customize) {
		foreach ($this->repeaters as $r) {
			extract($r, \EXTR_PREFIX_ALL, 'repeater');
			foreach (range(1, $repeater_count) as $i) {
				foreach ($repeater_fields as $f) {
					$type = $f[0];
					$slug = $f[1];
					$settingSlug = "{$this->slug}_{$i}_{$slug}";
					$wp_customize->add_setting($settingSlug);
					$this->$type($wp_customize, $settingSlug, "$repeater_title $i ".ucfirst($slug));
				}
			}
		}
	}

	public function addRepeater($fields, $count, $title) {
		$this->repeaters[] = compact('fields','count','title');
		return $this;
	}

	public function getFieldValue($slug, $type) {
		$value = get_theme_mod("{$this->slug}_$slug");

		if ($value) switch ($type) {
			case 'page': return get_post($value);
			case 'image': return wp_get_attachment_image($value);
		}

		return $value;
	}

	public function getValues() {
		$items = [];
		foreach ($this->repeaters as $r) {
			extract($r, \EXTR_PREFIX_ALL, 'repeater');
			$fields = F\pluck($repeater_fields, 1);
			foreach (range(1, $repeater_count) as $i) {
				$values = array_combine(
					$fields,
					F\map($fields, function($f, $j) use ($i, $repeater_fields) { return $this->getFieldValue("{$i}_{$f}", $repeater_fields[$j][0]); })
				);
				if (F\some($values, 'Functional\\id')) $items[] = $values;
			}
		}
		return $items;
	}

	public function register($wp_customize) {
		$wp_customize->add_section($this->slug, ['title' => $this->title]);
		$this->registerRepeaters($wp_customize);
	}

}
