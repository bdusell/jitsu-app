<?php

namespace Jitsu\App\Handlers;

class Always extends Callback {

	public function handle($data) {
		$this->trigger($data);
		return true;
	}
}
