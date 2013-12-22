<?php

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi-url/csv'));
define('INPUT_MISSING_DIR', datadir('/doi-url/csv-missing'));

define('OUTPUT_DIR', datadir('/doi-url/csv-merged'));

define('DOI_DIR', datadir('/doi/csv'));

$files = glob(DOI_DIR . '/*.csv.gz');
rsort($files);

foreach ($files as $file) {
    print "$file\n";

    $base = basename($file);

    $output = gzopen(OUTPUT_DIR . '/' . $base, 'w');

    foreach(array(INPUT_DIR, INPUT_MISSING_DIR) as $dir) {
        if (!file_exists($dir . '/' . $base)) {
            continue;
        }

        $input = gzopen($dir . '/' . $base, 'r');

        while (($row = fgetcsv($input)) !== false) {
            list($doi, $host, $url) = $row;
            fputcsv($output, array($doi, $url));
        }

        gzclose($input);
    }

    gzclose($output);
}
