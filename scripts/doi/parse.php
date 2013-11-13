<?php

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi/original'));
define('OUTPUT_DIR', datadir('/doi'));

$output = gzopen(OUTPUT_DIR . '/doi.csv.gz', 'w');

$oai = new OAIClient;
foreach (glob(INPUT_DIR . '/*.0.xml.gz') as $basefile) {
	$i = 0;
	$token = null;
	$filename = $basefile;

	do {
		print "$filename\n";

		list($xpath, $doc) = $oai->load($filename);
		$root = $oai->root($xpath, 'ListIdentifiers');

		foreach ($xpath->query('oai:header', $root) as $record) {
			if (valid($xpath, $record)) {
				$doi = $xpath->evaluate('string(oai:identifier)', $record);
				$doi = preg_replace('#^info:doi/#', '', $doi);
				fputcsv($output, array($doi));
			}
		}

		$token = $oai->token($xpath, $root);
		$filename = preg_replace('/0\.xml\.gz$/', ++$i . '.xml.gz', $basefile);
	} while ($token);
}

gzclose($output);

function valid($xpath, $record) {
	// record is valid if the comment contains 'type: journal_article'
  $comment = $xpath->evaluate('string(.//comment()[1])', $record);

  return strpos($comment, 'type: journal_article') !== false;
}
