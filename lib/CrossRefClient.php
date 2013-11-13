<?php

class CrossRefClient extends CurlClient {
	public function __construct() {
		parent::__construct();

		curl_setopt($this->curl, CURLOPT_VERBOSE, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($this->curl, CURLOPT_ENCODING, null);
		curl_setopt($this->curl, CURLOPT_NOBODY, true);
	}

	/**
	 * make a HEAD request to get the location
	 *
	 * @param string $doi
	 *
	 * @return mixed response
	 */
	public function locate($doi) {
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
