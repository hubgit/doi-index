<?php

/**
 * Interact with an OAI-PMH repository
 */
class OAIClient extends CurlClient
{
    /** @var string The base URL of the repository */
    public $base;

    /** @var array */
    protected $files = array();

    /**
     * Call the OAI-PMH interface.
     *
     * @param string $verb
     * @param array  $params
     * @param string $basefile
     *
     * @return array
     */
    public function fetch($verb, $params, $basefile)
    {
        $page = 0;
        $token = null;

        do {
            if ($token) {
                $params = array('resumptionToken' => $token);
            }

            $filename = $basefile . sprintf('%d.xml.gz', $page++);

            if (file_exists($filename)) {
                // TODO: error log
                return;
            }

            $params['verb'] = $verb;
            $output = gzopen($filename, 'w');
            $this->get($this->base, $params, $output);
            gzclose($output);

            list($xpath) = $this->load($filename);
            $root = $this->root($xpath, $verb);
            $token = $this->token($xpath, $root);
        } while ($token);
    }

    /**
     * Load the XML.
     *
     * @param $file
     *
     * @return array
     * @throws Exception
     */
    public function load($file)
    {
        if (!file_exists($file)) {
            throw new Exception('XML file not found: ' . $file);
        }

        $doc = new DOMDocument;
        $doc->load('compress.zlib://' . $file);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
        $xpath->registerNamespace('oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');

        return array($xpath, $doc);
    }

    /**
     * Fetch details of the OAI server
     */
    public function identify()
    {
        $params = array('verb' => 'Identify');

        $url = $this->base . '?' . http_build_query($params);

        $doc = new DOMDocument;
        $doc->load($url);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');

        $root = $this->root($xpath, $params['verb']);

        $identity = array();

        foreach ($root->childNodes as $node) {
            $identity[$node->nodeName] = $node->nodeValue;
        }

        return $identity;
    }


    /**
     * @param \DOMXPath $xpath
     * @param string    $verb
     *
     * @return mixed
     */
    public function root($xpath, $verb)
    {
        return $xpath->query('oai:' . $verb)->item(0);
    }

    /**
     * Parse a resumption token from the response.
     *
     * @param DOMXPath   $xpath
     * @param DOMElement $root
     *
     * @return string|null
     */
    public function token($xpath, $root)
    {
        $token = $xpath->evaluate('string(oai:resumptionToken)', $root);

        return $token ? : null;
    }
}
