<?php

namespace Jitsu\App;

/**
 * A generic collection of configuration settings.
 *
 * Extensions may add getter and setter functions by defining methods prefixed
 * with `get_` and `set_`.
 */
class Config {

	private $attrs = array();

	/**
	 * Initialize with a file name or an array of attributes.
	 */
	public function __construct() {
		foreach(func_get_args() as $arg) {
			$this->merge($arg);
		}
	}

	/**
	 * Read settings from a file.
	 *
	 * This simply evaluates a PHP file with this object assigned to
	 * `$config`.
	 */
	public function read($filename) {
		$config = $this;
		include $filename;
		return $this;
	}

	/**
	 * Set a variable.
	 */
	public function set($name, $value = null) {
		if(func_num_args() === 1) {
			$this->merge($name);
		} else {
			$this->_set_one($name, $value);
		}
		return $this;
	}

	/**
	 * Merge configuration settings.
	 *
	 * If a string is passed, reads settings from the named file.
	 */
	public function merge($arg) {
		if(is_string($arg)) {
			$this->read($arg);
		} else {
			$this->_set_many($name);
		}
		return $this;
	}

	public function __set($name, $value) {
		$this->_set_one($name, $value);
	}

	private function _set_many($attrs) {
		foreach($name as $key => $value) {
			$this->_set_one($name, $value);
		}
	}

	private function _set_one($name, $value) {
		$setter = 'set_' . $name;
		if(method_exists($this, $setter)) {
			$this->$setter($value);
		} else {
			$this->attrs[$name] = $value;
		}
	}

	/**
	 * Get a variable or a default value.
	 */
	public function get($name, $default = null) {
		$getter = 'get_' . $name;
		if(method_exists($this, $getter)) {
			return $this->$getter($default);
		} else {
			return (
				array_key_exists($name, $this->attrs) ?
				$this->attrs[$name] : $default
			);
		}
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function has($name) {
		return array_key_exists($name, $this->attrs);
	}

	public function __isset($name) {
		return $this->has($name);
	}
}
