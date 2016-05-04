<?php

namespace Jitsu\App\Handlers;

/**
 * Checks whether any earlier handlers matched the URL but not the method.
 *
 * Relies on the property `$data->available_methods`.
 */
class BadMethod extends Condition {

	public function matches($data) {
		return (bool) Util::requireProp($data, 'available_methods');
	}
}
