<?php

namespace Jitsu\App\Handlers;

/**
 * Does some pre-processing with `$data->config` before any other handlers run.
 *
 * Adds the properties `$data->route`, which is the relative path of the
 * request being routed, and `$data->available_methods`, which will be used to
 * store the methods of any handlers which match the URL but not the method.
 */
class Configure implements \Jitsu\App\Handler {

	public function handle($data) {
		$data->route = null;
		$data->available_methods = array();
		$config = Util::requireProp($data, 'config');
		$route = $config->removePath($data->request->path());
		$data->route = $route;
		if($route === null) {
			throw new \LogicException('misconfigured base path');
		}
		return false;
	}
}
