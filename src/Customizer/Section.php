<?php
namespace WordpressLib\Customizer;

use Functional as F;

class Section {

	public function __construct($slug, $title) {
		$this->slug = $slug;
		$this->title = $title;
		$this->fields = [];
		$this->repeaters = [];
		add_action('customize_register', [$this, 'register']);
	}

	public function addRepeater($fields, $count, $title, $optionType='option') {
		$this->repeaters[] = new Repeater(compact('fields','count','title','optionType'), $this->slug);
		return $this;
	}

	public function addFields($fields, $optionType='option') {
		foreach ($fields as $f) {
			$type = $f[0];
			$slug = $f[1];
			$title = isset($f[2]) ? $f[2] : ucfirst($slug);
			$attrs = isset($f[3]) ? $f[3] : [];
			$this->fields[] = new Field($this->slug, $type, $slug, $title, $optionType, $attrs);
		}
		return $this;
	}

	public function getValues() {
		$items = [];

		foreach (F\invoke($this->repeaters, 'getValues') as $repeater_items) {
			foreach ($repeater_items as $values) {
				if (F\some($values, 'Functional\\id')) $items[] = $values;
			}
		}

		$keys = F\pluck($this->fields, 'slug');
		$values = F\invoke($this->fields, 'getValue');
		if (F\some($values, 'Functional\\id')) $items[] = array_combine($keys, $values);

		return $items;
	}

	public function register($wp_customize) {
		$wp_customize->add_section($this->slug, ['title' => $this->title]);
		F\invoke($this->fields, 'register', [$wp_customize]);
		F\invoke($this->repeaters, 'register', [$wp_customize]);
	}

}
