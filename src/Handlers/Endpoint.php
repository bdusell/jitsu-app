<?php

namespace Jitsu\App\Handlers;

class Endpoint extends Route {

	private $method;

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
