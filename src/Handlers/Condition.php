<?php

namespace Jitsu\App\Handlers;

/**
 * Sub-class of `Callback` which fires only if the predicate `matches` passes.
 */
abstract class Condition extends Callback {

	public function handle($data) {
		if(($r = $this->matches($data))) {
			$this->trigger($data);
		}
		return $r;
	}

	/**
	 * @param object $data The argument to `handle()`.
	 * @return bool Whether to fire the callback and handle the request.
	 */
	abstract public function matches($data);
}
