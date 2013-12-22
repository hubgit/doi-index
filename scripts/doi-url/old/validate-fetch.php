<?php

require __DIR__ . '/../include.php';

define('OUTPUT_DIR', datadir('/doi-url/csv-missing'));

$input = fopen(datadir('/doi-url') . '/missing.csv', 'r');

$client = new CrossRefClient;

while (($row = fgetcsv($input)) !== false) {
    list($base, $doi) = $row;
    print "$doi\n";

    // TODO: log failures
    $url = $client->locate($doi);
    print "\t$url\n";

    $outputfile = OUTPUT_DIR . '/' . $base;
    print "\t$outputfile\n";
    $output = gzopen($outputfile, 'a');

    $host = parse_url($url, PHP_URL_HOST);
    fputcsv($output, array($doi, $host, $url));

    gzclose($output);
}
