<?php

namespace Jitsu\App\Handlers;

/**
 * Passes the request through a sub-router.
 *
 * Matches if and only if the sub-router matches. Keeps going if no sub-routes
 * match, even if the mount point matched.
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

		/* Match the mount point to the beginning of the route. */
		$saved_route = Util::requireProp($data, 'route');
		list($regex, $mapping) = Util::patternToStartRegex($this->mount_point);
		if(!preg_match($regex, $saved_route, $matches)) {
			return false;
		}

		/* Temporarily modify the route so that it is relative to the
		 * sub-router's mount point. */
		$saved_route = $route;
		$data->route = substr($saved_route, strlen($matches[0]));

		/* Push the matched URL parameters. */
		$parameters = Util::namedMatches($matches, $mapping);
		$had_params = \Jitsu\Util::hasProp($data, 'parameters');
		if($had_params) {
			$saved_parameters = $data->parameters;
			foreach($parameters as $key => $value) {
				$data->parameters[$key] = $value;
			}
		} else {
			$data->parameters = $parameters;
		}

		/* Run the sub-router. */
		try {
			$r = $this->router->run($data);
		} catch(\Exception $e) {
		}

		/* Restore the full route. */
		$data->route = $saved_route;

		/* Didn't match? Pop the parameters matched in the router's
		 * mount point. */
		if(isset($e) || !$r) {
			if($had_params) {
				$data->parameters = $saved_parameters;
			} else {
				unset($data->parameters);
			}
		}

		/* Re-throw any exception thrown by the sub-router. */
		if(isset($e)) throw $e;

		/* Stop iff the sub-router matched the route. */
		return $r;
	}
}
