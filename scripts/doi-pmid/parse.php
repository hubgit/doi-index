<?php

require __DIR__ . '/../include.php';

define('INPUT_DIR', datadir('/doi-pmid/original'));
define('OUTPUT_DIR', datadir('/doi-pmid'));

$output = gzopen(OUTPUT_DIR . '/doi-pmid.csv.gz', 'w');

foreach (glob(INPUT_DIR . '/*.xml.gz') as $file) {
	print "$file\n";

	$reader = new XMLReader;
	$reader->open('compress.zlib://' . $file);
	$reader->setParserProperty(XMLReader::LOADDTD, true);
	$reader->setParserProperty(XMLReader::VALIDATE, true);
	$reader->setParserProperty(XMLReader::SUBST_ENTITIES, true);

	$items = array();
	$item = null;
	$list = null;

	while ($reader->read()) {
		switch ($reader->nodeType) {
			case XMLREADER::ELEMENT:
				switch ($reader->localName) {
					case 'DocSum':
						$item = array();
					break;

					case 'Item':
						$name = $reader->getAttribute('Name');
						$type = $reader->getAttribute('Type');

						// Integer|Date|String|Structure|List|Flags|Qualifier|Enumerator|Unknown
						switch ($type) {
							case 'List':
								$item[$name] = array();
								$list = $name;
							break;

							case 'String':
							case 'Date':
							case 'Integer':
								$value = $reader->readString();

								if ($value === '') {
									$value = null;
								} else if ($type == 'Integer') {
									$value = (int) $value;
								}

								if ($list) {
									$item[$list][$name] = $value;
								} else {
									$item[$name] = $value;
								}
							break;
						}

					break;
				}
			break;

			case XMLREADER::END_ELEMENT:
				switch ($reader->localName) {
					case 'DocSum':
						$items[] = $item;
						$item = null;
					break;

					case 'Item':
						if ($reader->getAttribute('Type') == 'List') {
							$list = null;
						}
					break;
				}
			break;
		}
	}

	foreach ($items as $item) {
		$ids = $item['ArticleIds'];
		$pmcid = isset($ids['pmc']) ? preg_replace('/^PMC/', '', $ids['pmc']) : null;
		fputcsv($output, array($ids['doi'], $ids['pubmed'], $pmcid));
	}
}

gzclose($output);


