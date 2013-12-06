<?php

date_default_timezone_set('UTC');

/**
 * @param string $class
 */
function __autoload($class)
{
    /** @noinspection PhpIncludeInspection */
    include __DIR__ . '/../lib/' . $class . '.php';
}

/**
 * Build the path to a data directory, and make sure it exists
 *
 * @param string $suffix
 *
 * @return string
 */
function datadir($suffix)
{
    $dir = __DIR__ . '/../data' . $suffix;

    if (!file_exists($dir)) {
        print "Creating $dir\n";
        mkdir($dir, 0777, true);
    }

    return $dir;
}
