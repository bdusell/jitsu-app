<?php

namespace Jitsu\App\Handlers;

/**
 * Checks whether any earlier handlers matched the URL but not the method.
 */
class BadMethod extends Condition {

	public function matches($data) {
		return (bool) Util::requireProp($data, 'available_methods');
	}
}
