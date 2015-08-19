<?php

namespace Jitsu\App\Handlers;

class Callback implements \Jitsu\App\Handler {

	private $callback;

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
