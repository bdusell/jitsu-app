#!/usr/bin/env php
<?php

// vendor/jitsu/app/bin/ -> vendor/autoload.php
require dirname(dirname(dirname(__DIR__))) . '/autoload.php';

call_user_func(function() use($argv) {

	$script_name = array_shift($argv);

	$show_usage = function() use($script_name) {
		echo <<<TXT
Usage: $script_name [-c|--config <config-file> ...] <php-file>

  Inject configuration settings into a PHP template.

TXT
		;
	};

	$fail = function() use($show_usage) {
		$show_usage();
		exit(1);
	};

	$config = new \Jitsu\App\SiteConfig;
	$php_file = null;
	while(($arg = array_shift($argv)) !== null) {
		if($arg === '-h' || $arg === '--help') {
			$show_usage();
			exit(0);
		} elseif($arg === '-c' || $arg === '--config') {
			if(!$argv) $fail();
			$config->read(array_shift($argv));
		} elseif($php_file === null) {
			$php_file = $arg;
		} else {
			$fail();
		}
	}
	if($php_file === null) $fail();
	\Jitsu\Util::template($php_file, array('config' => $config));
});
