<?php

/**
 * Class CrossRefClient
 */
class CrossRefClient extends CurlClient
{
    /**
     * Set some extra cURL options
     */
    public function __construct()
    {
        parent::__construct();

        curl_setopt_array(
            $this->curl,
            array(
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_ENCODING => null,
                CURLOPT_NOBODY => true,
                CURLOPT_VERBOSE => true,
            )
        );
    }

    /**
     * Make a HEAD request to get the location (without redirection)
     *
     * @param string $doi
     *
     * @return mixed response
     */
    public function locate($doi)
    {
        $url = 'http://dx.doi.org/' . rawurlencode($doi);
        print "$url\n";

        curl_setopt($this->curl, CURLOPT_URL, $url);

        $this->headers = array();

        $result = curl_exec($this->curl);
        $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        //print_r($this->headers);

        switch ($code) {
            case 301:
            case 302:
            case 303:
                return trim($this->headers['location']);

            default:
                print "Error $code\n";
                print_r($result, true);

                return null;
        }
    }

    // TODO: content negotiation for metadata formats and styles
}
