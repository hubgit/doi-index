<?php

require __DIR__ . '/../include.php';

define('OUTPUT_DIR', datadir('/doi/original'));

$oai = new OAIClient;
$oai->base = 'http://oai.crossref.org/OAIHandler';

// http://oai.crossref.org/OAIHandler?verb=Identify -> earliestDatestamp
$earliest = new DateTime('2007-02-12T12:00:00Z');
$datetime = new DateTime('-2 DAYS');

$set = 'J';

do {
	$date = $datetime->format('Y-m-d');

	$params = array(
		'set' => $set,
		'from' => $date,
		'until' => $date,
	);

	$oai->fetch('ListIdentifiers', $params, OUTPUT_DIR . '/' . $date);

	$datetime->modify('-1 DAY');
} while ($datetime > $earliest);

