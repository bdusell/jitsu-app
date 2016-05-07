<?php

namespace Jitsu\App;

/**
 * An extensible sub-router which can be mounted in another router.
 */
abstract class SubRouter extends Router {

	public function __construct() {
		parent::__construct();
		$this->initialize();
	}
}
