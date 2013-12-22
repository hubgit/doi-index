<?php

/**
 * Read the CSV file of URLs, normalise the host
 * and save the URLs in a single file per host
 *
 * Input: data/doi-url/csv/{Y-m-d}.csv.gz
 * (one CSV file per day; doi, host, url)
 *
 * Output: data/doi-url/hosts/{host}.csv
 */

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi-url/csv'));
define('OUTPUT_DIR', datadir('/doi-url/hosts'));

$outputs = array();

foreach (glob(INPUT_DIR . '/*.csv.gz') as $file) {
    print "$file\n";
    $input = gzopen($file, 'r');

    while (($row = fgetcsv($input)) !== false) {
        list($doi, $host, $url) = $row;

        // e.g. articles.example.com => example.com
        if (preg_match('/([^\.]+\.(com|org|net))$/', $host, $matches)) {
            $host = $matches[1];
        }

        // e.g. www.example.co.uk => example.co.uk
        $host = preg_replace('/^www\./', '', $host);

        $hostfile = preg_replace('/[^a-z0-9\.-]/i', '_', $host);
        $output = OUTPUT_DIR . '/' . $hostfile . '.csv';
        file_put_contents($output, $url . "\n", FILE_APPEND);
    }
}
