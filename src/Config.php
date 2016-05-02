<?php

namespace Jitsu\App;

/**
 * An object whose properties store configuration settings.
 *
 * Usage is very simple. It behaves just like an `stdObject`, where properties
 * can be set and accessed at will.
 *
 *     $config = new Config(['a' => 1]);
 *     $config->b = 2;
 *     echo $config->a, ' ', $config->b, "\n";
 * 
 * Sub-classes may add dynamic getter and setter functions for specific
 * properties by defining methods prefixed with `get_` and `set_`, followed by
 * the name of the simulated property.
 *
 * Configuration settings may be read from any PHP file which assigns properties
 * to a pre-defined variable called `$config`. For example:
 *
 * **config.php**
 *
 *     $config->a = 1;
 *     $config->b = 2;
 *
 * To read the file:
 *
 *     $config = new Config('config.php');
 */
class Config {

	private $attrs = array();

	/**
	 * Initialize with the name of a PHP file or an `array` of properties.
	 *
	 * @param string|array $args,...
	 */
	public function __construct(/* $args,... */) {
		foreach(func_get_args() as $arg) {
			$this->merge($arg);
		}
	}

	/**
	 * Read settings from a PHP file.
	 *
	 * This simply evaluates a PHP file with this object assigned to
	 * `$config`.
	 *
	 * @param string $filename
	 * @return $this
	 */
	public function read($filename) {
		$config = $this;
		include $filename;
		return $this;
	}

	/**
	 * Set/add a property.
	 *
	 * @param string|array $name The name of the property to set.
	 *        Alternatively, pass a single `array` to set multiple
	 *        properties.
	 * @param mixed $value
	 * @return $this
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
	 * Set/add multiple properties.
	 *
	 * @param string|array $arg The name of a file to read or an array
	 *        of properties to set.
	 */
	public function merge($arg) {
		if(is_string($arg)) {
			$this->read($arg);
		} else {
			$this->_set_many($arg);
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
	 * Get a property.
	 *
	 * @param string $name The name of the property.
	 * @param mixed $default Default value to get if the property does not
	 *                       exist.
	 */
	public function get($name, $default = null) {
		$getter = 'get_' . $name;
		if(method_exists($this, $getter)) {
			return $this->$getter($default);
		} else {
			return (
				array_key_exists($name, $this->attrs) ?
				$this->attrs[$name] :
				$default
			);
		}
	}

	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * Tell whether a certain property exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function has($name) {
		return array_key_exists($name, $this->attrs);
	}

	public function __isset($name) {
		return $this->has($name);
	}
}
