<?php

namespace Jitsu\App\Handlers;

class BadMethod extends Condition {

	public function matches($data) {
		return (bool) Util::requireProp($data, 'available_methods');
	}
}
