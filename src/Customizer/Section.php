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

	public function addRepeater($fields, $count, $title) {
		$this->repeaters[] = new Repeater(compact('fields','count','title'), $this->slug);
		return $this;
	}

	public function getValues() {
		$items = [];
		foreach (F\invoke($this->repeaters, 'getValues') as $repeater_items) {
			foreach ($repeater_items as $values) {
				if (F\some($values, 'Functional\\id')) $items[] = $values;
			}
		}
		return $items;
	}

	public function register($wp_customize) {
		$wp_customize->add_section($this->slug, ['title' => $this->title]);
		F\invoke($this->repeaters, 'register', [$wp_customize]);
	}

}
