<?php

namespace IFW\Http;

use IFW;

/**
 *
 */
class Client {

	/**
	 * Client version
	 *
	 * @constant	string
	 */
	const VERSION = '0.1';

	/**
	 * User agent displayed in http requests
	 *
	 * @constant	string
	 */
	const USERAGENT = 'IFW Http Client';

	/**
	 * CardDAV server url
	 *
	 * @var	string
	 */
	private $host = null;

	/**
	 * Authentication string
	 *
	 * @var	string
	 */
	private $auth = null;

	/**
	 * Authentication: username
	 *
	 * @var	string
	 */
	private $username = null;

	/**
	 * Authentication: password
	 *
	 * @var	string
	 */
	private $password = null;

	/**
	 * CardDAV server connection (curl handle)
	 *
	 * @var	resource
	 */
	private $curl;

	/**
	 * Constructor
	 * Sets the CardDAV server url
	 *
	 * @param	string	$host	CardDAV server url
	 */
	public function __construct($host) {
		$this->host = $host;
	}

	/**
	 * Sets authentication information
	 *
	 * @param	string	$username	CardDAV server username
	 * @param	string	$password	CardDAV server password
	 * @return	void
	 */
	public function setAuth($username, $password) {
		$this->username = $username;
		$this->password = $password;
		$this->auth = $username . ':' . $password;
	}

	/**
	 * Checks if the server is reachable
	 *
	 * @return	boolean
	 */
	public function checkConnection() {
		$result = $this->request($this->host, 'OPTIONS');

		if ($result['http_code'] === 200) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Curl initialization
	 *
	 * @return void
	 */
	private function curlInit() {
		if (empty($this->curl)) {
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_HEADER, true);
//			curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
//			curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl, CURLOPT_USERAGENT, self::USERAGENT . '/' . self::VERSION);

			if ($this->auth !== null) {
				curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt($this->curl, CURLOPT_USERPWD, $this->auth);
			}
		}
	}

	/**
	 * Query the HTTP server
	 *
	 * @param	string	$path				Path on the server
	 * @param	string	$method				HTTP method like (OPTIONS, GET, HEAD, POST, PUT, DELETE, TRACE, COPY, MOVE)
	 * @param	string	$body			Content for CardDAV queries
	 * 	 
	 * @return Response
	 */
	public function request($path, $method = "GET", $body = null, $headers = []) {
		$this->curlInit();

		curl_setopt($this->curl, CURLOPT_URL, $this->host . $path);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);

		if ($body !== null) {
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
		} else {
			curl_setopt($this->curl, CURLOPT_POST, false);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, null);
		}

		curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

		$responseHeaders = [];

		curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
			if (preg_match('/([\w-]+): (.*)/i', $header, $matches)) {
				$responseHeaders[strtolower($matches[1])] = $matches[2];
			}
			return strlen($header);
		});

		$completeResponse = curl_exec($this->curl);


		$requestHeaders = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
		$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
		$status = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
//		$header = trim(substr($complete_response, 0, $header_size));
		$responseText = substr($completeResponse, $header_size);

		$completeRequest = $requestHeaders . "\n\n" . $body;

		IFW::app()->debug($completeRequest, 'dav');
		IFW::app()->debug($completeResponse, 'dav');

		return $this->createResponse($status, $responseText, $responseHeaders);
	}

	/**
	 * 
	 * @param type $status
	 * @param type $responseText
	 * @param type $responseHeaders
	 * @return \IFW\Http\Response
	 */
	protected function createResponse($status, $responseText, $responseHeaders) {
		return new Response($status, $responseText, $responseHeaders);
	}

	/**
	 * Destructor
	 * Close curl connection if it's open
	 *
	 * @return	void
	 */
	public function __destruct() {
		if (!empty($this->curl)) {
			curl_close($this->curl);
		}
	}

}
