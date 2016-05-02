<?php

namespace Jitsu\App\Handlers;

/**
 * A handler that always returns true.
 */
class Always extends Callback {

	public function handle($data) {
		$this->trigger($data);
		return true;
	}
}
