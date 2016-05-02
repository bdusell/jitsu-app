<?php

namespace Jitsu\App\Handlers;

/**
 * Passes the request through a sub-router.
 */
class Mount implements \Jistu\App\Handler {

	private $mount_point;
	private $router;

	/**
	 * @param string $mount_point
	 * @param \Jitsu\App\Router $router
	 */
	public function __construct($mount_point, $router) {
		$this->mount_point = $mount_point;
		$this->router = $router;
	}

	public function handle($data) {
		$route = Util::requireProp($data, 'route');
		list($regex, $mapping) = Util::patternToStartRegex($this->mount_point);
		$r = (bool) preg_match($regex, $route, $matches);
		if($r) {
			$parameters = Util::namedMatches($matches, $mapping);
			$had_params = \Jitsu\Util::hasProp($data, 'parameters');
			if($had_params) {
				$saved_parameters = $data->parameters;
				$data->parameters = $parameters + $saved_parameters;
			} else {
				$data->parameters = $parameters;
			}
			$saved_route = $data->route;
			$data->route = substr($saved_route, strlen($matches[0]));
			$r = $this->router->run($data);
			$data->route = $saved_route;
			if(!$r) {
				if($had_params) {
					$data->parameters = $saved_parameters;
				} else {
					unset($data->parameters);
				}
			}
		}
		return $r;
	}
}
