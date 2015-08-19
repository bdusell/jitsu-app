<?php

namespace Jitsu\App\Handlers;

class Route extends Condition {

	private $route;

	public function __construct($route, $callback) {
		parent::__construct($callback);
		$this->route = $route;
	}

	public function matches($data) {
		$route = Util::requireProp($data, 'route');
		list($regex, $mapping) = Util::patternToRegex($this->route);
		$r = (bool) preg_match($regex, $route, $matches);
		$named_matches = array();
		for($i = 1, $n = count($matches); $i < $n; ++$i) {
			$named_matches[$mapping[$i - 1]] = urldecode($matches[$i]);
		}
		$data->parameters = $named_matches;
		return $r;
	}
}
