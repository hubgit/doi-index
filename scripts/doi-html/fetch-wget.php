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

$files = glob(INPUT_DIR . '/*.csv.gz');
natsort($files);
$files = array_reverse($files);

define('USER_AGENT', 'doi-index/0.1 (+http://goo.gl/AejefJ)');

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

        $output = $dir . '/' . md5($doi) . '.html';

        if (file_exists($output)) {
            continue;
        }

        $command = sprintf('wget -e robots=off --connect-timeout=10 --read-timeout=60 --wait=1 --warc-file=%s --user-agent=%s --load-cookies=%s --save-cookies=%s --output-document=%s %s',
            escapeshellarg($output),
            escapeshellarg(USER_AGENT),
            escapeshellarg('/tmp/cookies.txt'),
            escapeshellarg('/tmp/cookies.txt'),
            escapeshellarg($output),
            escapeshellarg($url));

        exec($command);
    }

    gzclose($input);
}
