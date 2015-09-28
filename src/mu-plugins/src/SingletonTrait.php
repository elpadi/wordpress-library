<?php

trait SingletonTrait {

	private static $_instance;

	public static function instance() {
		if (!self::$_instance) {
			$class = get_called_class();
			self::$_instance = new $class();
		}
		return self::$_instance;
	}

}
