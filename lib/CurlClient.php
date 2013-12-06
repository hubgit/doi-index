<?php

/**
 * A wrapper for cURL
 */
class CurlClient {
	/** @var resource */
	public $curl;

	/** @var array */
	public $headers;

	/**
	 * create a cURL instance
	 */
	public function __construct() {
		$this->curl = curl_init();

		curl_setopt_array($this->curl, array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_VERBOSE => true,
			CURLOPT_HEADERFUNCTION => array($this, 'header'),
			CURLOPT_ENCODING => 'gzip,deflate',
			//CURLOPT_HTTPHEADER => array('Accept: application/xml,text/xml'),
		));
	}

    /**
     * Make a GET request
     * Either save the response to a file, or return it
     *
     * @param string $url
     * @param array  $params
     * @param null   $file
     * @param int    $tries
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function get($url, $params = array(), $file = null, $tries = 0)
	{
		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}

		print "$url\n";

		$this->headers = array();

		curl_setopt($this->curl, CURLOPT_URL, $url);

		if (is_null($file)) {
			curl_setopt($this->curl, CURLOPT_FILE, STDOUT);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		} else {
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, false);
			curl_setopt($this->curl, CURLOPT_FILE, $file);
		}

		$result = curl_exec($this->curl);
		$code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

		switch ($code) {
			case 200:
				return is_null($file) ? $result : true;

			case 429: // rate limit
				if ($tries == 5) {
					throw new Exception('Rate limited too many times');
				}

				$this->delay();

				return $this->get($url, array(), $file, ++$tries);

			default:
				$message = sprintf('Response not OK: %d %s', $code, $result);

				throw new Exception($message);
		}
	}

    /**
     * Store response headers in an array
     *
     * @param $curl
     * @param $header
     *
     * @return int header length
     */
	protected function header(
        /** @noinspection PhpUnusedParameterInspection */
        $curl, $header) {
		$parts = preg_split('/:\s+/', $header, 2);

		if (isset($parts[1])) {
			list($name, $value) = $parts;
			$this->headers[strtolower($name)] = $value;
		}

		return strlen($header);
	}

	/**
	 * delay if rate limit is reached
	 */
	protected function delay() {
		if (!isset($this->headers['x-rate-limit-reset'])) {
			exit('Rate limited, but no rate limit header found');
		}

		$delay = $this->headers['x-rate-limit-reset'] - time();

		if ($delay < 10) {
			$delay = 60 * 15; // 15 minute delay if the given delay seems unreasonably small (can be due to server time differences)
		}

		printf('Sleeping for %d seconds', $delay);
		sleep($delay);
	}
}
