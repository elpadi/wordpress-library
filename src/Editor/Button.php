<?php
namespace WordpressLib\Editor;

abstract class Button {

	protected $name;
	protected $icon;

	public function __construct() {
		add_filter('mce_buttons', [$this, 'registerButtonFilter']);
		add_filter('mce_external_plugins', [$this, 'registerPluginFilter']);
	}

	public function registerButtonFilter($buttons) {
	   array_push($buttons, $this->name);
	   return $buttons;
	}
		 
	public function registerPluginFilter($plugin_array) {
	   $plugin_array[$this->name] = $this->getPluginUrl();
	   return $plugin_array;
	}

	abstract protected function getPluginUrl();

}
