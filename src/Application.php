<?php

namespace Jitsu\App;

/**
 * An extensible router class.
 */
abstract class Application extends Router {

	public function __construct() {
		parent::__construct();
		$this->handler(new Handlers\Configure);
		$this->initialize();
	}

	abstract public function initialize();

	public function respond($request, $response, $config) {
		return $this->run((object) array(
			'request' => $request,
			'response' => $response,
			'config' => $config
		));
	}

	public static function main($app, $config) {
		return $app->respond(
			new \Jitsu\Http\CurrentRequest,
			new \Jitsu\Http\CurrentResponse,
			$config
		);
	}

	public function callback($callback) {
		$this->handler(new Handlers\Callback($callback));
	}

	public function setNamespace($value) {
		$this->handler(new Handlers\SetNamespace($value));
	}

	public function route($route, $callback) {
		$this->handler(new Handlers\Route($route, $callback));
	}

	public function endpoint($method, $route, $callback) {
		$this->handler(new Handlers\Endpoint($method, $route, $callback));
	}

	public function get($route, $callback) {
		$this->endpoint('GET', $route, $callback);
	}

	public function post($route, $callback) {
		$this->endpoint('POST', $route, $callback);
	}

	public function put($route, $callback) {
		$this->endpoint('PUT', $route, $callback);
	}

	public function delete($route, $callback) {
		$this->endpoint('DELETE', $route, $callback);
	}

	public function badMethod($callback) {
		$this->handler(new Handlers\BadMethod($callback));
	}

	public function notFound($callback) {
		$this->handler(new Handlers\Always($callback));
	}

	public function error($callback) {
		$this->errorHandler(new Handlers\Always($callback));
	}
}
