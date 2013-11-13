<?php

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi'));
define('OUTPUT_DIR', datadir('/doi-url'));

$client = new CrossRefClient;

$files = glob(INPUT_DIR . '/*.csv.gz');
rsort($files);

foreach ($files as $i => $file) {
	print "$file\n";

	$outputFile = OUTPUT_DIR . '/' . basename($file);

	if (file_exists($outputFile)) {
		continue;
	}

	$input = gzopen($file, 'r');
	$output = gzopen($outputFile, 'w');

	while (($line = fgetcsv($input)) !== false) {
		list($doi) = $line;

		// TODO: log failures
		$url = $client->locate($doi);
		print "\t$url\n";

		$host = parse_url($url, PHP_URL_HOST);
		fputcsv($output, array($doi, $host, $url));
	}

	gzclose($input);
	gzclose($output);

	if ($i % 100 === 0) {
		sleep(5);
	}
}