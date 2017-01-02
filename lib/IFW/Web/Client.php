<?php

namespace IFW\Web;

use Exception;
use IFW;
use IFW\Fs\File;

/**
 * Curl base HTTP client
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Client {

	private $_curl;
	private $_cookieFile;
	private $_lastDownloadUrl;
	public $lastHeaders = array();

	/**
	 * Key value array of params that will be sent with each request.
	 * 
	 * @var array 
	 */
	public $baseParams;
	
	public $baseUrl;

	public function __construct() {

		$this->baseParams = array();

		if (!function_exists('curl_init')) {
			throw new Exception("Could not initialized HTTP client because PHP is configured withour CURL support.");
		}

		$this->_curl = curl_init();
		curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->_curl, CURLOPT_ENCODING, "UTF-8");

		if (!empty(IFW::app()->getConfig()->curl_proxy)) {
			curl_setopt($this->_curl, CURLOPT_PROXY, IFW::app()->getConfig()->curl_proxy);
		}

		$this->setCurlOption(CURLOPT_USERAGENT, "Group-Office HttpClient (curl)");

		//set ajax header for Group-Office
//		$this->setCurlOption(CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest"));
	}

	public function enableCookies() {

		$this->_cookieFile = IFW::app()->getConfig()->getTempFolder() . '/cookie-'.  uniqid().'.txt';
		curl_setopt($this->_curl, CURLOPT_COOKIEJAR, $this->_cookieFile);
		curl_setopt($this->_curl, CURLOPT_COOKIEFILE, $this->_cookieFile);
	}

	public function dontVerifySSL() {
		curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, false);
	}

	/**
	 * Set additional curl options. See php.net for details.
	 * 
	 * @param int $option
	 * @param mixed $value 
	 */
	public function setCurlOption($option, $value) {
		curl_setopt($this->_curl, $option, $value);
	}

	/**
	 * Make a POST request to any URL
	 * 
	 * @param string $url
	 * @param string $params POST parameters
	 * @param string Response of the server.
	 * @throws Exception 
	 */
	public function request($url, $params = array()) {

		$this->_initRequest($url, $params);

		curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($this->_curl);

		$error = curl_error($this->_curl);
		if (!empty($error)) {
			throw new Exception("curl error: " . $error);
		}

		return $response;
	}


	private function _initRequest($url, $params) {
		$params = array_merge($this->baseParams, $params);

		$this->lastHeaders = array();
		
		if(isset($this->baseUrl)) {
			$url = $this->baseUrl.$url;
		}
		
		IFW::app()->debug("CURL Request: ".$url.' params: '.var_export($params, true));

		curl_setopt($this->_curl, CURLOPT_URL, $url);
		curl_setopt($this->_curl, CURLOPT_POST, !empty($params));
		if (!empty($params)) {
			curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $params);
		}
	}

	public function getLastDownloadedFilename() {

		if (isset($this->lastHeaders['Content-Disposition']) && preg_match('/filename="(.*)"/', $this->lastHeaders['Content-Disposition'], $matches)) {
			return $matches[1];
		}

		$filename = File::utf8Basename($this->_lastDownloadUrl);

		if (!empty($filename))
			return $filename;

		return false;
	}

	/**
	 * Download a file
	 * 
	 * @param string $url
	 * @param File $outputFile
	 * @param array $params
	 * @return boolean
	 */
	public function downloadFile($url, File $outputFile, $params = array()) {

		$this->_lastDownloadUrl = $url;

		$this->_initRequest($url, $params);

		$fp = fopen($outputFile->getPath(), 'w');

		curl_setopt($this->_curl, CURLOPT_FILE, $fp);
		curl_setopt($this->_curl, CURLOPT_HEADERFUNCTION, function ($ch, $header) {
			if (preg_match('/([\w-]+): (.*)/i', $header, $matches)) {
				$this->lastHeaders[$matches[1]] = $matches[2];
			}
			return strlen($header);
		});

		$response = curl_exec($this->_curl);
		fclose($fp);
		
		$error = curl_error($this->_curl);
		if (!empty($error)) {
			throw new Exception("curl error: " . $error);
		}

		if ($outputFile->getSize()) {
			return true;
		} else {
			return false;
		}
	}

	public function getHttpCode() {
		return curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
	}

	public function getContentType() {
		return curl_getinfo($this->_curl, CURLINFO_CONTENT_TYPE);
	}

	public function __destruct() {
		if ($this->_curl) {
			curl_close($this->_curl);
		}

		if (isset($this->_cookieFile) && file_exists($this->_cookieFile)) {
			unlink($this->_cookieFile);
		}
	}

}
