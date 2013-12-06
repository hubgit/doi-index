<?php

/**
 * For each day's DOIs, fetch the HTML of each resource from the registered URL
 *
 * Input:
 * data/doi-url/csv/{Y-m-d}.csv.gz
 * (one CSV file per day; doi, host, url)
 *
 * Output:
 * data/doi-html/original/{Y-m-d}/{doi_base_64}.html.gz
 * data/doi-html/original/{Y-m-d}/{doi_base_64}.json (record)
 */

require __DIR__ . '/../include.php';

// data/doi-url/csv/{Y-m-d}.csv.gz
define('INPUT_DIR', datadir('/doi-url/csv'));
define('OUTPUT_DIR', datadir('/doi-html/original'));

$client = new CurlClient;
curl_setopt($client->curl, CURLOPT_HEADER, array(
    'Accept: text/html,application/xhtml+xml',
));

$files = glob(INPUT_DIR . '/*.csv.gz');
natsort($files);
$files = array_reverse($files);

//$hosts = array();
foreach ($files as $file) {
    print "$file\n";

    $date = basename($file, '.csv.gz');

    $dir = OUTPUT_DIR . '/' . $date;
    if (file_exists($dir)) {
        continue;
    }
    mkdir($dir);

    $input = gzopen($file, 'r');

    while (($row = fgetcsv($input)) !== false) {
        list($doi, $host, $url) = $row;

        /*
        if (!isset($hosts[$host])) {
            $hosts[$host] = 0;
        }

        if ($hosts[$host] == 5) {
            continue;
        }

        $hosts[$host]++;
        */

        $md5 = md5($doi);
        $output = $dir . '/' . $md5 . '.html.gz';
        $report = $dir . '/' . $md5 . '.json';

        if (file_exists($report) && file_exists($output)) {
            continue;
        }

        // data/doi-html/original/{Y-m-d}/{md5_url}.html.gz
        $outputFile = gzopen($output, 'w');

        try {
            $client->get($url, array(), $outputFile);
        } catch (Exception $e) {
            print $e->getMessage() . "\n";
        }

        gzclose($outputFile);

        $data = array(
            'doi' => $doi,
            'url' => $url,
            'info' => curl_getinfo($client->curl),
            'headers' => array_map('trim', $client->headers),
        );

        print_r($data);

        // data/doi-html/original/{Y-m-d}/{md5_url}.json
        file_put_contents($report, json_encode($data));
    }

    gzclose($input);
}
