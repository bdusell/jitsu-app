<?php

namespace Jitsu\App\Handlers;

/**
 * Callable-to-handler adapter class.
 *
 * Affected by the property `$data->app_namespace`, which dictates the default
 * namespace of any callbacks that are passed as strings.
 */
class Callback implements \Jitsu\App\Handler {

	private $callback;

	/**
	 * @param callable $callback
	 */
	public function __construct($callback) {
		$this->callback = $callback;
	}

	public function handle($data) {
		return self::trigger($data);
	}

	public function trigger($data) {
		$callback = $this->callback;
		if(is_string($callback)) {
			$namespace = \Jitsu\Util::getProp($data, 'app_namespace');
			if($namespace !== null) {
				$callback = Util::normalizeNamespace($namespace) . $callback;
			}
		}
		return call_user_func($callback, $data);
	}
}
