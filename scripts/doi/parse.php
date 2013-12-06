<?php

/**
 * For each day, read the XML files of identifiers fetched from CrossRef
 * and save the DOIs to a CSV file
 *
 * Input: data/doi/original/{Y-m-d}/identifiers.{page}.xml.gz
 * (one directory per day, one XML file per page)
 *
 * Output: data/doi/csv/{Y-m-d}.csv.gz
 * (one CSV file per day; doi)
 */

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi/original'));
define('OUTPUT_DIR', datadir('/doi/csv'));

$oai = new OAIClient;

// data/doi/{Y-m-d}
$date_dirs = glob(INPUT_DIR . '/*', GLOB_ONLYDIR);

foreach ($date_dirs as $date_dir) {
    // the page number, used in the file name
    $page = 0;

    // the resumption token, used in the request
    $token = null;

    // get the date for the output file from the input directory name
    $date = basename($date_dir);

    // data/doi/csv/{Y-m-d}.csv.gz
    $output = gzopen(OUTPUT_DIR . '/' . $date . '.csv.gz', 'w');

    do {
        // data/doi/original/{Y-m-d}/identifiers.{page}.xml.gz
        $input_file = $date_dir . sprintf('/identifiers.%d.xml.gz', $page++);
        print $input_file . "\n";

        // if the fetching had failed for some reason, the file might not be found
        if (!file_exists($input_file)) {
			// TODO: error log
			exit("File $input_file does not exist\n");
		}

        /** @var $xpath DOMXPath */
		list($xpath, $doc) = $oai->load($input_file);
		$root = $oai->root($xpath, 'ListIdentifiers');

		foreach ($xpath->query('oai:header', $root) as $record) {
			// record is valid if the comment contains 'type: journal_article'
			$comment = $xpath->evaluate('string(.//comment()[1])', $record);

			if (strpos($comment, 'type: journal_article') === false) {
				continue;
			}

            // just store the DOI of each record
            $doi = $xpath->evaluate('string(oai:identifier)', $record);
			$doi = preg_replace('#^info:doi/#', '', $doi);
			fputcsv($output, array($doi));
		}

        // the resumption token is in the response if there's another page
        $token = $oai->token($xpath, $root);
	} while ($token);

    // data/doi/csv/{Y-m-d}.csv.gz
    gzclose($output);
}
