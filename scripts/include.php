<?php

date_default_timezone_set('UTC');

function __autoload($class) {
    include __DIR__ . '/../lib/' . $class . '.php';
}

function datadir($suffix) {
	$dir = __DIR__ . '/../data' . $suffix;

	if (!file_exists($dir)) {
		mkdir($dir, 0777, true);
	}

	return $dir;
}