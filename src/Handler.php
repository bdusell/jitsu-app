<?php

namespace Jitsu\App;

/**
 * A route handler interface.
 */
interface Handler {

	/**
	 * React to a request.
	 *
	 * @param object $data Some datum which is passed from handler to
	 *                     handler.
	 * @return bool Whether this handler has matched the route and the
	 *              router should stop routing.
	 */
	public function handle($data);
}
