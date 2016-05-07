<?php

namespace Jitsu\App;

/**
 * An extensible HTTP request router.
 *
 * Like `BasicRouter` but with fancy shortcut methods.
 */
abstract class Router extends BasicRouter {

	/**
	 * Override this method to configure routes and handlers on this
	 * router.
	 */
	abstract protected function initialize();

	/**
	 * Dispatch a request to this router.
	 *
	 * @param \Jitsu\Http\RequestBase $request The HTTP request object.
	 *        This will be made available to handlers via `$data->request`.
	 * @param \Jitsu\Http\ResponseBase $response The HTTP response object
	 *        with which the application code will interact. This will be
	 *        made available to handlers via `$data->response`.
	 * @param \Jitsu\App\SiteConfig $config Configuration settings for the
	 *        router. This will be made available to handlers via
	 *        `$data->config`.
	 */
	public function respond($request, $response, $config) {
		return $this->run((object) array(
			'request' => $request,
			'response' => $response,
			'config' => $config
		));
	}

	/**
	 * Shorthand for `respond` which invokes the router with the current
	 * HTTP request and response.
	 *
	 * @param \Jitsu\App\SiteConfig $config Configuration settings for the
	 *        router.
	 */
	public static function main($config) {
		$class = get_called_class();
		$app = new $class;
		return $app->respond(
			new \Jitsu\Http\CurrentRequest,
			new \Jitsu\Http\CurrentResponse,
			$config
		);
	}

	/**
	 * Add a callback to the request handler queue.
	 *
	 * The callback will receive a single `stdObject` argument (`$data`)
	 * which has been passed through earlier handlers. The handler may read
	 * properties from this object to access data from earlier handlers and
	 * assign properties to pass data to later handlers. The router
	 * initially adds the properties `request`, `response`, and `config`.
	 *
	 * The handler should return `true` if routing should cease with this
	 * handler (indicating a match) or `false` if the router should
	 * continue to attempt to match later routes. A handler can perform
	 * some action or add to `$data` without returning `true`, so that it
	 * merely serves to communicate with later handlers.
	 *
	 * Callbacks are executed in the same order they are added.
	 *
	 * @param callable $callback A callback which accepts a single
	 *        `stdObject` argument and returns a `bool` to indicate whether
	 *        it has handled the request and the router should stop
	 *        dispatching.
	 */
	public function callback($callback) {
		$this->handler(new Handlers\Callback($callback));
	}

	/**
	 * Set the namespace which will be automatically prefixed to the names
	 * of callbacks passed to all handler functions.
	 *
	 * Function names are accepted as callbacks in all of the callback
	 * registration methods here. If all of your callbacks are under one
	 * namespace, this can be used to avoid repeating the namespace in all
	 * function names.
	 *
	 * @param string $value
	 */
	public function setNamespace($value) {
		$this->handler(new Handlers\SetNamespace($value));
	}

	/**
	 * Handle all requests to a certain path.
	 *
	 * The path is specified as a pattern which may contain named
	 * parameters. The following syntax is supported:
	 *
	 * * `:name`, which captures a portion of a path segment called `name`.
	 *   This will not match slash (`/`) characters.
	 * * `*name`, which captures a portion of text called `name` which can
	 *   span multiple path segments. This will match any character.
	 * * `(optional)`, where the portion enclosed by `()` characters may
	 *   optionally be present. Any pattern syntax may appear inside the
	 *   optional portion.
	 *
	 * Any named parameters will automatically be URL-decoded and stored in
	 * `$data->parameters`, an array mapping parameter names to captured
	 * values. The key-value pairs will occur in the same order as the
	 * parameters were specified.
	 *
	 * @param string $route A path pattern.
	 * @param callable $callback
	 */
	public function route($route, $callback) {
		$this->handler(new Handlers\Route($route, $callback));
	}

	/**
	 * Handle all requests to a certain combination of method and path.
	 *
	 * @param string $method The method (`GET`, `POST`, etc.).
	 * @param string $route A path pattern.
	 * @param callable $callback
	 */
	public function endpoint($method, $route, $callback) {
		$this->handler(new Handlers\Endpoint($method, $route, $callback));
	}

	/**
	 * Handle a GET request to a certain path.
	 *
	 * @param string $route A path pattern.
	 * @param callable $callback
	 */
	public function get($route, $callback) {
		$this->endpoint('GET', $route, $callback);
	}

	/**
	 * Handle a POST request to a certain path.
	 *
	 * @param string $route A path pattern.
	 * @param callable $callback
	 */
	public function post($route, $callback) {
		$this->endpoint('POST', $route, $callback);
	}

	/**
	 * Handle a PUT request to a certain path.
	 *
	 * @param string $route A path pattern.
	 * @param callable $callback
	 */
	public function put($route, $callback) {
		$this->endpoint('PUT', $route, $callback);
	}

	/**
	 * Handle a DELETE request to a certain path.
	 *
	 * @param string $route A path pattern.
	 * @param callable $callback
	 */
	public function delete($route, $callback) {
		$this->endpoint('DELETE', $route, $callback);
	}

	/**
	 * Mount a sub-router at a certain path.
	 *
	 * Stops routing if and only if the sub-router matches. Routing
	 * continues if the sub-router does not match, even if the mount point
	 * matched.
	 *
	 * @param string $route A path pattern indicating where the sub-router
	 *                      will be mounted.
	 * @param \Jitsu\App\Router $router
	 */
	public function mount($route, $router) {
		$this->handler(new Handlers\Mount($route, $router));
	}

	/**
	 * Handles any request whose URL was matched in an earlier handler but
	 * was not handled because the method did not match.
	 *
	 * The property `$data->matched_methods` will contain the list of
	 * allowed methods for this URL.
	 *
	 * @param callable $callback
	 */
	public function badMethod($callback) {
		$this->handler(new Handlers\BadMethod($callback));
	}

	/**
	 * Handles any request which was not matched in an earlier handler.
	 *
	 * @param callable $callback
	 */
	public function notFound($callback) {
		$this->handler(new Handlers\Always($callback));
	}

	/**
	 * Handles any exceptions thrown by request handlers.
	 *
	 * The property `$data->exception` will be set to the exception thrown.
	 *
	 * @param callable $callback A callback which accepts a single
	 *                           `stdObject` argument.
	 */
	public function error($callback) {
		$this->errorHandler(new Handlers\Always($callback));
	}
}
