<?php

namespace Jitsu\App\Handlers;

/**
 * Matches a particular URL pattern.
 */
class Route extends Condition {

	private $route;

	/**
	 * The route can accept a simple pattern syntax: `:id` to match a path
	 * segment named `id`, `*path` to match a portion of the URL named
	 * `path` which may span multiple segments , and `(optional)` to an
	 * optional section. Named parameters will be collected in the array
	 * `$data->parameters`.
	 *
	 * @param string $route A URL pattern. 
	 */
	public function __construct($route, $callback) {
		parent::__construct($callback);
		$this->route = $route;
	}

	public function matches($data) {
		$route = Util::requireProp($data, 'route');
		list($regex, $mapping) = Util::patternToRegex($this->route);
		$r = (bool) preg_match($regex, $route, $matches);
		if($r) {
			Util::ensureArray($data, 'parameters');
			$parameters = Util::namedMatches($matches, $mapping);
			foreach($parameters as $key => $value) {
				$data->parameters[$key] = $value;
			}
		}
		return $r;
	}
}
