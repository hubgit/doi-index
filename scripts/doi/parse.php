<?php

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi/original'));
define('OUTPUT_DIR', datadir('/doi'));

$output = gzopen(OUTPUT_DIR . '/doi.csv.gz', 'w');

$oai = new OAIClient;

$iterator = new FilesystemIterator(INPUT_DIR, FilesystemIterator::SKIP_DOTS);

foreach ($iterator as $fileinfo) {
	print $fileinfo->getPathname();

	if (!$fileinfo->isDir()) {
		continue;
	}

	$dir = $fileinfo->getPathname();

	$i = 0;
	$token = null;

	do {
		$filename = sprintf('%s/identifiers.%d.xml.gz', $dir, $i++);
		print "$filename\n";

		if (!file_exists($filename)) {
			// TODO: error log
			exit("File $filename does not exist\n");
		}

		list($xpath, $doc) = $oai->load($filename);
		$root = $oai->root($xpath, 'ListIdentifiers');

		foreach ($xpath->query('oai:header', $root) as $record) {
			// record is valid if the comment contains 'type: journal_article'
			$comment = $xpath->evaluate('string(.//comment()[1])', $record);

			if (strpos($comment, 'type: journal_article') === false) {
				continue;
			}

			$doi = $xpath->evaluate('string(oai:identifier)', $record);
			$doi = preg_replace('#^info:doi/#', '', $doi);
			fputcsv($output, array($doi));
		}

		$token = $oai->token($xpath, $root);
	} while ($token);
}

gzclose($output);

