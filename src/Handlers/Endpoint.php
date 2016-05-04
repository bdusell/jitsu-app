<?php

namespace Jitsu\App\Handlers;

/**
 * Matches a combination of HTTP method and URL.
 *
 * If the URL matches but the method does not, the method is added to
 * `$data->available_methods`.
 */
class Endpoint extends Route {

	private $method;

	/**
	 * @param string $method
	 * @param string $route
	 * @param callable $callback
	 */
	public function __construct($method, $route, $callback) {
		parent::__construct($route, $callback);
		$this->method = strtoupper($method);
	}

	public function matches($data) {
		if(parent::matches($data)) {
			if($data->request->method() === $this->method) {
				return true;
			} else {
				$data->available_methods[] = $this->method;
			}
		}
		return false;
	}
}
