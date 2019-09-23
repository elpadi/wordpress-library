<?php
namespace WordpressLib\Customizer;

use Functional as F;

class Repeater {

	public function __construct($options, $sectionSlug) {
		$this->options = $options;
		$this->sectionSlug = $sectionSlug;
	}

	public function getFieldValue($slug, $type) {
		return Control::getValue("{$this->sectionSlug}_$slug", $type);
	}

	public function getValues() {
		$items = [];
		extract($this->options, \EXTR_PREFIX_ALL, 'repeater');
		$fields = F\pluck($repeater_fields, 1);
		foreach (range(1, $repeater_count) as $i) {
			$values = array_combine(
				$fields,
				F\map($fields, function($f, $j) use ($i, $repeater_fields) { return $this->getFieldValue("{$i}_{$f}", $repeater_fields[$j][0]); })
			);
			if (F\some($values, 'Functional\\id')) $items[] = $values;
		}
		return $items;
	}

	public function register($wp_customize) {
		extract($this->options, \EXTR_PREFIX_ALL, 'repeater');
		foreach (range(1, $repeater_count) as $i) {
			foreach ($repeater_fields as $f) {
				$type = $f[0];
				$slug = $f[1];
				$title = isset($f[2]) ? $f[2] : ucfirst($slug);
				$settingSlug = "{$this->sectionSlug}_{$i}_{$slug}";
				$wp_customize->add_setting($settingSlug);
				new Control($wp_customize, $this->sectionSlug, $settingSlug, "$repeater_title $i ".$title, $type);
			}
		}
	}

}
