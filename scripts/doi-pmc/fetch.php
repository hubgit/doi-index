<?php

require __DIR__ . '/../include.php';

define('OUTPUT_DIR', datadir('/doi-pmc/original'));

$oai = new OAIClient();
$oai->base = 'http://www.pubmedcentral.nih.gov/oai/oai.cgi';

// http://www.pubmedcentral.nih.gov/oai/oai.cgi?verb=Identify -> earliestDatestamp
$earliest = new DateTime('1999-01-01T12:00:00Z');
$datetime = new DateTime('-2 DAYS');

do {
    $date = $datetime->format('Y-m-d');

    $params = array(
        'metadataPrefix' => 'pmc',
        'from' => $date,
        'until' => $date,
    );

    $oai->fetch('ListRecords', $params, OUTPUT_DIR . '/' . $date);

    $datetime->modify('-1 DAY');
} while ($datetime > $earliest);

