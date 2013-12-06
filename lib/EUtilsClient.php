<?php

class EUtilsClient extends CurlClient
{
    // use a specific server so that history paging doesn't fail when round robin changes
    /** @string $server */
    public $server = 'http://eutils.be-md.ncbi.nlm.nih.gov';

    /** @string $db */
    public $db;

    public function search($term)
    {
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

        $doc = new DOMDocument;
        $doc->validateOnParse = true;
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

    public function summary($term, $dir)
    {
        $search = $this->search($term);
        print_r($search);

        if (!$search['count']) {
            return;
        }

        $url = $this->server . '/entrez/eutils/esummary.fcgi';

        $offset = 0;
        $rows = 1000;

        do {
            $params = array(
                'retmode' => 'xml',
                'db' => $this->db,
                'webenv' => $search['webenv'],
                'query_key' => $search['querykey'],
                'retstart' => $offset,
                'retmax' => $rows,
            );

            $filename = $dir . '/' . $offset . '.xml.gz';
            $output = gzopen($filename, 'w');

            $this->get($url, $params, $output);
            gzclose($output);

            $offset += $rows;
        } while ($offset < $search['count']);
    }
}
