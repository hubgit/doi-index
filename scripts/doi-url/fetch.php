<?php

/**
 * For each day's DOIs, make a HEAD request to CrossRef
 * and save the registered URL
 *
 * Input: data/doi/csv/{Y-m-d}.csv.gz
 * (one CSV file per day; doi)
 *
 * Output: data/doi-url/csv/{Y-m-d}.csv.gz
 * (one CSV file per day; doi, url)
 */

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi/csv'));
define('OUTPUT_DIR', datadir('/doi-url/csv'));

$client = new CrossRefClient;

$files = glob(INPUT_DIR . '/*.csv.gz');
rsort($files);

foreach ($files as $i => $file) {
    print $file . "\n";

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
        print "\t" . $url . "\n";

        fputcsv($output, array($doi, $url));
    }

    gzclose($input);
    gzclose($output);
}
