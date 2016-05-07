<?php

namespace Jitsu\App;

/**
 * An extensible top-level HTTP request router.
 */
abstract class Application extends Router {

	/**
	 * Automatically adds a configuration handler.
	 */
	public function __construct() {
		parent::__construct();
		$this->handler(new Handlers\Configure);
		$this->initialize();
	}
}
