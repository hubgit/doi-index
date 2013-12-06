<?php

require __DIR__ . '/../include.php';

define('OUTPUT_DIR', datadir('/doi-pmc/original'));

$oai = new OAIClient();
$oai->base = 'http://www.pubmedcentral.nih.gov/oai/oai.cgi';

// get the OAI-PMH server identity, for the earliestDatestamp info
$identity = $oai->identify();

// set the earliest date for which a record is available
$earliest = new DateTime($identity['earliestDatestamp'] . 'T12:00:00Z');
printf("Earliest record: %s\n", $earliest->format(DATE_ATOM));

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

