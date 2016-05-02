<?php

namespace Jitsu\App\Handlers;

class Util {

	public static function requireProp($data, $name) {
		if(!property_exists($data, $name)) {
			throw new \InvalidArgumentException("$name property is missing");
		}
		return $data->$name;
	}

	public static function normalizeNamespace($value) {
		$value = trim($value, '\\');
		return $value === '' ? '\\' : "\\$value\\";
	}

	public static function patternToRegexCore($pat) {
		$mapping = array();
		$regex = preg_replace_callback(
			'#(?::([A-Za-z_]\\w*($)?))|(?:\\*([A-Za-z_]\\w*))|(\\()|(\\))|(.)#',
			function($matches) use(&$mapping) {
				if(isset($matches[6])) {
					return preg_quote($matches[6], '#');
				} elseif(isset($matches[5])) {
					return ')?';
				} elseif(isset($matches[4])) {
					return '(?:';
				} elseif(isset($matches[3])) {
					$mapping[] = $matches[3];
					return '(.*?)';
				} elseif(isset($matches[1])) {
					$mapping[] = $matches[1];
					return isset($matches[2]) ? '([^/]*)' : '([^/]+)';
				}
			},
			$pat
		);
		return array($regex, $mapping);
	}

	public static function patternToRegex($pat) {
		list($regex, $mapping) = self::patternToRegexCore($pat);
		return array('#^' . $regex . '$#', $mapping);
	}

	public static function patternToStartRegex($pat) {
		list($regex, $mapping) = self::patternToRegexCore($pat);
		return array('#^' . $regex . '#', $mapping);
	}

	public static function namedMatches($matches, $mapping) {
		$named_matches = array();
		for($i = 1, $n = count($matches); $i < $n; ++$i) {
			$named_matches[$mapping[$i - 1]] = urldecode($matches[$i]);
		}
		return $named_matches;
	}

	public static function ensureArray($data, $name) {
		if(!\Jitsu\Util::hasProp($data, $name)) {
			$data->$name = array();
		}
	}
}
