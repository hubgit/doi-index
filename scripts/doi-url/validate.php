<?php

require __DIR__ . '/../include.php';

define('DOI_DIR', datadir('/doi/csv'));

define('INPUT_DIR', datadir('/doi-url/csv'));
define('OUTPUT_DIR', datadir('/doi-url'));

$output = fopen(OUTPUT_DIR . '/missing.csv', 'w');

$files = glob(DOI_DIR . '/*.csv.gz');
rsort($files);

foreach ($files as $file) {
	print "$file\n";

	$base = basename($file);

	// read in fetched urls
	$seen = array();
	$doifile = INPUT_DIR . '/' . $base;
	$input = gzopen($doifile, 'r');

	if (!$input) {
		printf("Could not open input file: %s\n", $doifile);
		continue;
	}

	while (($row = fgetcsv($input)) !== false) {
		list($doi, $host, $url) = $row;
		$seen[$doi] = true;
	}
	printf("\t%d URLs\n", count($seen));
	fclose($input);

	// read in all DOIs for the same day and find those without URLs
	$missing = 0;
	$input = gzopen($file, 'r');
	while (($row = fgetcsv($input)) !== false) {
		list($doi) = $row;

		if (!isset($seen[$doi])) {
			fputcsv($output, array($base, $doi));
			$missing++;
		}
	}

	printf("\t%d missing\n", $missing);
}
