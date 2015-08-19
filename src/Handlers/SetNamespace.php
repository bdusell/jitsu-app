<?php

namespace Jitsu\App\Handlers;

class SetNamespace implements \Jitsu\App\Handler {

	private $app_namespace;

	public function __construct($app_namespace) {
		$this->app_namespace = Util::normalizeNamespace($app_namespace);
	}

	public function handle($data) {
		$data->app_namespace = $this->app_namespace;
	}
}
