<?php

namespace Jitsu\App\Handlers;

class Configure implements \Jitsu\App\Handler {

	public function handle($data) {
		$config = Util::requireProp($data, 'config');
		$route = $config->removePath($data->request->path());
		if($route === null) {
			throw new \LogicException('misconfigured base path');
		}
		$data->route = $route;
		$data->available_methods = array();
		return false;
	}
}
