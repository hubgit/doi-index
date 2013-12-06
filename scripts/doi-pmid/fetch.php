<?php

require __DIR__ . '/../include.php';

define('OUTPUT_DIR', datadir('/doi-pmid/original'));

$client = new EUtilsClient;
$client->db = 'pubmed';

// http://www.ncbi.nlm.nih.gov/pubmed/?term=doi[sb] -> last page
// TODO: fetch the earliest result with a search query
$earliest = new DateTime('1880-01-01T12:00:00Z');
$datetime = new DateTime('-2 DAYS');

do {
    // [crdt] = created date (date entered into PubMed post-2008, date published pre-2008)
    $term = sprintf('doi[sb] AND %s[crdt]', $datetime->format('Y/m/d'));

    $dir = OUTPUT_DIR . '/' . $datetime->format('Y-m-d');

    if (!file_exists($dir)) {
        mkdir($dir);

        try {
            $client->summary($term, $dir);
        } catch (Exception $e) {
            // remove the directory if there's an error
            foreach (glob($dir . '/*.xml.gz') as $file) {
                print "Deleting $file\n";
                unlink($file);
            }
            rmdir($dir);
        }
    }

    $datetime->modify('-1 DAY');
} while ($datetime > $earliest);
