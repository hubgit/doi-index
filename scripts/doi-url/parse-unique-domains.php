<?php

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi-url/csv'));
define('OUTPUT_DIR', datadir('/doi-url'));

$hosts = array();

foreach (glob(INPUT_DIR . '/*.csv.gz') as $file) {
	print "$file\n";
	$input = gzopen($file, 'r');

	while (($row = fgetcsv($input)) !== false) {
		list($doi, $host, $url) = $row;

		$hosts[$host]++;
	}
}

arsort($hosts);

$output = fopen(OUTPUT_DIR . '/domains.csv', 'w');
foreach ($hosts as $host => $count) {
	fputcsv($output, array($host, $count));
}
fclose($output);
