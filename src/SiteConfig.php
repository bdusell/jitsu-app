<?php

namespace Jitsu\App;

/**
 * A subclass of `Config` specialized for websites.
 *
 * Adds the following properties:
 *
 * * `base_url`: The external URL at which the router is mounted. Tied to the
 *   properties `scheme`, `host`, and `path`.
 * * `scheme`: The scheme or protocol used by the site (`http` or `https`).
 * * `host`: The host name of the site (such as `example.com`).
 * * `path`: The path section of the `base_url`.
 * * `base_path`: Like `path`, but formatted so that it always begins and ends
 *    with a slash.
 * * `locale`: The locale of the currently running PHP script. When setting,
 *   use an array of values to indicate a series of fallbacks.
 */
class SiteConfig extends Config {

	/**
	 * Set the base URL of the site.
	 *
	 * If the `scheme`, `host`, or `path` can be parsed from the new value,
	 * they are set accordingly.
	 *
	 * @param string $url
	 */
	public function set_base_url($url) {
		$parts = parse_url($url);
		foreach(array('scheme', 'host', 'path') as $name) {
			if(array_key_exists($name, $parts)) {
				$this->set($name, $parts[$name]);
			}
		}
	}

	/**
	 * Get the base URL.
	 *
	 * Consists of `<scheme>://<host>/<path>`.
	 *
	 * @return string
	 */
	public function get_base_url() {
		return $this->scheme . '://' . $this->host . $this->base_path;
	}

	/**
	 * Get the `path`, formatted so that it always begins and ends with a
	 * slash.
	 *
	 * @return string
	 */
	public function get_base_path() {
		$path = trim($this->path, '/');
		return $path === '' ? '/' : '/' . $path . '/';
	}

	/**
	 * Given a relative path, append it to the `path` to form an absolute
	 * path.
	 *
	 * This has the exception that if both are the empty string, it returns
	 * the empty string.
	 *
	 * @param string $rel_path
	 * @return string
	 */
	public function makePath($rel_path) {
		return $this->base_path . $rel_path;
	}

	/**
	 * Strip off the `path` from an absolute path, or return `null` if the
	 * path does not match.
	 *
	 * @param string $abs_path
	 * @return string|null
	 */
	public function removePath($abs_path) {
		return \Jitsu\StringUtil::removePrefix($abs_path, $this->base_path);
	}

	/**
	 * Given a relative path, append it to the `base_url` to form an
	 * absolute URL.
	 *
	 * A small, special case: if the configured `path` is set to the empty
	 * string, then an empty `$rel_path` will produce a URL consisting of
	 * the domain with _no_ trailing slash (e.g. `http://www.example.com`).
	 * This is unlike setting `path` to `/`, which will _always_ produce a
	 * URL with a trailing slash, even when `$rel_path` is empty (e.g.
	 * `http://www.example.com/`). The former is techinically malformed,
	 * although it may be desired, and browsers will generally still accept
	 * it as a hyperlink.
	 *
	 * @param string $rel_path
	 * @return string
	 */
	public function makeUrl($rel_path) {
		return (
			$this->scheme . '://' .
			$this->host .
			(
				$rel_path === '' && $this->path === '' ?
				'' :
				$this->makePath($rel_path)
			)
		);
	}

	/**
	 * Set the locale.
	 *
	 * If the value is an array of strings, each string serves as a
	 * fallback for the preceding ones in case they are not available.
	 *
	 * @param string|string[] $value
	 */
	public function set_locale($value) {
		setlocale(LC_ALL, $value);
	}

	/**
	 * Get the locale.
	 *
	 * @return string
	 */
	public function get_locale() {
		return setlocale('0');
	}
}
