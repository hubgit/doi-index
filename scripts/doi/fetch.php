<?php

/**
 * For each day, fetch the list of registered identifiers
 * from CrossRef's OAI server, and store the XML files.
 *
 * Input: CrossRef OAI server (ListIdentifiers)
 *
 * Output: data/doi/original/{Y-m-d}/identifiers.{page}.xml.gz
 * (one directory per day, one XML file per page)
 */

require __DIR__ . '/../include.php';

// output to /data/doi/original
define('OUTPUT_DIR', datadir('/doi/original'));

// fetching data from CrossRef's OAI-PMH server
$oai = new OAIClient;
$oai->base = 'http://oai.crossref.org/OAIHandler';

// set the timeout to 1 hour, as the response can be slow
curl_setopt($oai->curl, CURLOPT_TIMEOUT, 600);

// get the OAI-PMH server identity, for the earliestDatestamp info
$identity = $oai->identify();

// set the earliest date for which a record is available
$earliest = new DateTime($identity['earliestDatestamp'] . 'T12:00:00Z');
printf("Earliest record: %s\n", $earliest->format(DATE_ATOM));

// the latest date to fetch records for
// (two days ago, as records might still be added for yesterday)
$datetime = new DateTime('-2 DAYS');

// journal article records are in set 'J'
$set = 'J';

do {
    // save each day's files in a separate folder
    $date = $datetime->format('Y-m-d');
    $dir = OUTPUT_DIR . '/' . $date;

    // if this day's data has already been fetched, continue
    if (!file_exists($dir)) {
        mkdir($dir);

        $params = array(
            'set' => $set,
            'from' => $date,
            'until' => $date,
        );

        try {
            // files will be $dir/identifiers.{page}.xml.gz
            $prefix = $dir . '/identifiers.';
            $oai->fetch('ListIdentifiers', $params, $prefix);
        } catch (Exception $e) {
            // if something goes wrong, remove all the files and date directory
            // TODO: error log
            foreach (glob($dir . '/identifiers.*.xml.gz') as $file) {
                //unlink($file);
            }
            //rmdir($dir);
        }
    }

    // fetch the previous day's data, until the earliestDatestamp
    $datetime->modify('-1 DAY');
} while ($datetime > $earliest);

