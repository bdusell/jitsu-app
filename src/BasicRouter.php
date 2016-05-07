<?php

namespace Jitsu\App;

/**
 * Basic router class.
 *
 * The router consists of two queues: the normal request handler queue, and
 * the error handler queue. The router calls request handlers in the same
 * order they were registered until one of them returns `true`. If a handler
 * throws an exception, control passes irreversibly to the error handler queue,
 * which behaves in the same way but has no rescue strategy for exceptions.
 */
class BasicRouter {

	private $handlers = array();
	private $error_handlers = array();

	public function __construct() {
	}

	/**
	 * Add a handler to the request handler queue.
	 *
	 * @param \Jitsu\App\Handler $handler
	 */
	public function handler($handler) {
		$this->handlers[] = $handler;
	}

	/**
	 * Add a handler to the error handler queue.
	 *
	 * The `$data` argument to the handler will have the property
	 * `exception` set to the exception that was thrown.
	 *
	 * @param \Jitsu\App\Handler $handler
	 */
	public function errorHandler($handler) {
		$this->error_handlers[] = $handler;
	}

	/**
	 * Invoke the router with some datum to be passed from handler to
	 * handler.
	 *
	 * @param object $data Some datum to be passed from handler to handler.
	 * @return bool Whether the invocation was handled, meaning that
	 *              routing ended when some request handler or error
	 *              handler returned `true`.
	 * @throws \Exception Any exception thrown by a request handler that
	 *                    was not handled in the error handler queue, or
	 *                    any exception thrown from an error handler.
	 */
	public function run($data) {
		foreach($this->handlers as $handler) {
			try {
				if($handler->handle($data)) {
					return true;
				}
			} catch(\Exception $e) {
				$data->exception = $e;
				foreach($this->error_handlers as $error_handler) {
					if($error_handler->handle($data)) {
						return true;
					}
				}
				throw $e;
			}
		}
		return false;
	}
}
