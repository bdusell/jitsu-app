<?php

namespace Jitsu\App\Handlers;

/**
 * Sets the default namespace for callbacks.
 */
class SetNamespace implements \Jitsu\App\Handler {

	private $app_namespace;

	/**
	 * @param string $app_namespace
	 */
	public function __construct($app_namespace) {
		$this->app_namespace = Util::normalizeNamespace($app_namespace);
	}

	public function handle($data) {
		$data->app_namespace = $this->app_namespace;
	}
}
