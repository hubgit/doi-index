<?php

/**
 * Interact with an OAI-PMH repository
 */
class OAIClient extends CurlClient {
	/** @var string The base URL of the repository */
	public $base;

	/** @var array */
	protected $files = array();

	/**
	 * Load the XML.
	 */
	public function load($file) {
		if (!file_exists($file)) {
			throw new Exception('XML file not found: ' . $file);
		}

		$doc = new DOMDocument;
		//$doc->preserveWhiteSpace = false;
		$doc->load('compress.zlib://' . $file, LIBXML_DTDLOAD | LIBXML_NOENT | LIBXML_NONET);
		//$doc->formatOutput = true;

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
		$xpath->registerNamespace('oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
		$xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');

		return array($xpath, $doc);
	}

	/**
	 * Call the OAI-PMH interface.
     *
	 * @param array $params
	 * @param string|null $token resumptionToken
	 * @return array
	 */
	public function fetch($verb, $params, $basefile) {
		$page = 0;
		$token = null;
		$files = array();

		do {
			if ($token) {
				$params = array('resumptionToken' => $token);
			}

			$filename = sprintf('%s.%d.xml.gz', $basefile, $page++);

			if (file_exists($filename)) {
				return;
			}

			$files[] = $filename;
			$output = gzopen($filename, 'w');

			$params['verb'] = $verb;

			try {
				$this->get($this->base, $params, $output);
				gzclose($output);

				list($xpath, $doc) = $this->load($filename);
				$root = $this->root($xpath, $verb);
			} catch (Exception $e) {
				gzclose($output);

				foreach ($files as $filename) {
					print "Deleting $filename\n";
					unlink($filename);
				}

				throw $e;
			}
		} while ($token = $this->token($xpath, $root));
	}

	public function root($xpath, $verb) {
		return $xpath->query('oai:' . $verb)->item(0);
	}

	/**
	 * Parse a resumption token from the response.
	 * @param DOMXPath $xpath
	 * @param DOMElement $root
	 * @return string|null
	 */
	public function token($xpath, $root) {
		$token = $xpath->evaluate('string(oai:resumptionToken)', $root);

		return $token ?: null;
	}
}