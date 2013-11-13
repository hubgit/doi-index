<?php

abstract class EUtilsClient extends CurlClient {
	// use a specific server so that history paging doesn't fail when round robin changes
	/** @string $server */
	public $server = 'http://eutils.be-md.ncbi.nlm.nih.gov';

	/** @string $db */
	public $db;

	public function search($term) {
		$params = array(
			'db' => $this->db,
			'term' => $term,
			'retmax' => 0,
			'retstart' => 0,
			'retmode' => 'xml',
			'usehistory' => 'y',
		);

		$url = $this->server . '/entrez/eutils/esearch.fcgi';

		$result = $this->get($url, $params);

		list($type) = preg_split('/\s*;\s*/', $this->headers['content-type'], 2);

		if ($type !== 'text/xml') {
			throw new Exception('Unexpected content type: ' . $this->headers['content-type']);
		}

		$doc = new DOMDocument;
		$doc->loadXML($result, LIBXML_DTDLOAD | LIBXML_NOENT | LIBXML_NONET);

		if (!$doc->validate()) {
			throw new Exception('Invalid XML');
		}

		$xpath = new DOMXPath($doc);

		return array(
			'count' => $xpath->evaluate('number(Count)'),
			'webenv' => $xpath->evaluate('string(WebEnv)'),
			'querykey' => $xpath->evaluate('string(QueryKey)'),
		);
	}

	public function summary($term, $basefile) {
		$filename = sprintf('%s.%d.xml.gz', $basefile, $offset);

		if (file_exists($filename)) {
			return;
		}

		$search = $this->search($term);
		print_r($search);

		if (!$search['count']) {
			return;
		}

		$url = $this->server . '/entrez/eutils/esummary.fcgi';

		$offset = 0;
		$rows = 1000;
		$this->files = array();

		do {
			$filename = sprintf('%s.%d.xml.gz', $basefile, $offset);
			$output = gzopen($filename, 'w');
			$this->files[] = $filename;

			$params = array(
				'retmode' => 'xml',
				'db' => $this->db,
				'webenv' => $search['webenv'],
				'query_key' => $search['querykey'],
				'retstart' => $offset,
				'retmax' => $rows,
			);

			try {
				$this->get($url, $params, $output);
				gzclose($output);
			} catch (Exception $e) {
				gzclose($output);

				foreach ($this->files as $filename) {
					print "Deleting $filename\n";
					unlink($filename);
				}

				throw $e;
			}

			$offset += $rows;
		} while ($offset < $search['count']);
	}
}
