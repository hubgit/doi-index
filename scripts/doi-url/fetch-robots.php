<?php

require __DIR__ . '/../include.php';

define('OUTPUT_DIR', datadir('/doi-url/robots'));

$input = fopen(datadir('/doi-url') . '/domains.csv', 'r');

while (($row = fgetcsv($input)) !== false) {
	list($host, $count) = $row;

	if (!$host) {
		continue;
	}

	$host = strtolower($host);
	$hostfile = preg_replace('/[^a-z0-9\.-]/i', '_', $host);
	$output = OUTPUT_DIR . '/' . $hostfile . '.txt';

	$url = 'http://' . $host . '/robots.txt';
	print "$url\n";
	copy($url, $output);
}
