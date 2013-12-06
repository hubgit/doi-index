<?php

$output = gzopen(__DIR__ . '/orcid-dois.csv.gz', 'w');

$i = 0;

$iterator = new DirectoryIterator(__DIR__ . '/in_progress/xml/');
$total = iterator_count($iterator);
$iterator->rewind();

/** @var $fileinfo SplFileInfo */
foreach ($iterator as $fileinfo) {
    if (++$i % 1000 === 0) {
        print "$i of $total\n";
    }

    if (!$fileinfo->isFile()) {
        continue;
    }

    if ($fileinfo->getExtension() !== 'xml') {
        continue;
    }

    $file = $fileinfo->getPathname();
    //print "$file\n";

    $doc = new DOMDocument;
    $doc->load($file);

    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('o', 'http://www.orcid.org/ns/orcid');
    $nodes = $xpath->query(
        'o:orcid-profile/o:orcid-activities/o:orcid-works/o:orcid-work/o:work-external-identifiers/o:work-external-identifier[o:work-external-identifier-type="doi"]/o:work-external-identifier-id'
    ); // LOL

    if (!$nodes->length) {
        continue;
    }

    $orcid = $xpath->evaluate('string(o:orcid-profile/o:orcid)');

    $seen = array();
    foreach ($nodes as $node) {
        $doi = $node->nodeValue;

        // DOIs are not case-sensitive
        $doi = mb_strtolower($doi);

        // ignore DOIs containing newlines
        if (strpos($doi, "\n") !== false) {
            continue;
        }

        // remove http://dx.doi.org/ from the start of DOIs
        $doi = preg_replace('#^http://dx\.doi\.org/#i', '', $doi);

        // only store unique DOIs for each profile
        if (!isset($seen[$doi])) {
            fputcsv($output, array($orcid, $doi));
            $seen[$doi] = true;
        }
    }
}
