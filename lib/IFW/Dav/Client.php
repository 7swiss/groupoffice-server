<?php

namespace IFW\Dav;

use Exception;
use SimpleXMLElement;
use XMLWriter;

/**
 * CardDAV PHP
 *
 * Simple CardDAV query
 * --------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->setAuth('username', 'password');
 * echo $carddav->get();
 *
 *
 * Simple vCard query
 * ------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->setAuth('username', 'password');
 * echo $carddav->getVcard('0126FFB4-2EB74D0A-302EA17F');
 *
 *
 * XML vCard query
 * ------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->setAuth('username', 'password');
 * echo $carddav->getXmlVcard('0126FFB4-2EB74D0A-302EA17F');
 *
 *
 * Check CardDAV server connection
 * -------------------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->setAuth('username', 'password');
 * var_dump($carddav->checkConnection());
 *
 *
 * CardDAV delete query
 * --------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->setAuth('username', 'password');
 * $carddav->delete('0126FFB4-2EB74D0A-302EA17F');
 *
 *
 * CardDAV add query
 * --------------------
 * $vcard = 'BEGIN:VCARD
 * VERSION:3.0
 * UID:1f5ea45f-b28a-4b96-25as-ed4f10edf57b
 * FN:Christian Putzke
 * N:Christian;Putzke;;;
 * EMAIL;TYPE=OTHER:christian.putzke@graviox.de
 * END:VCARD';
 *
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->setAuth('username', 'password');
 * $vcard_id = $carddav->add($vcard);
 *
 *
 * CardDAV update query
 * --------------------
 * $vcard = 'BEGIN:VCARD
 * VERSION:3.0
 * UID:1f5ea45f-b28a-4b96-25as-ed4f10edf57b
 * FN:Christian Putzke
 * N:Christian;Putzke;;;
 * EMAIL;TYPE=OTHER:christian.putzke@graviox.de
 * END:VCARD';
 *
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->setAuth('username', 'password');
 * $carddav->update($vcard, '0126FFB4-2EB74D0A-302EA17F');
 *
 *
 * CardDAV debug
 * -------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->enableDebug();
 * $carddav->setAuth('username', 'password');
 * $carddav->get();
 * var_dump($carddav->getDebug());
 *
 *
 * CardDAV server list
 * -------------------
 * DAViCal:						https://example.com/{resource|principal|username}/{collection}/
 * Apple Addressbook Server:	https://example.com/addressbooks/users/{resource|principal|username}/{collection}/
 * memotoo:						https://sync.memotoo.com/cardDAV/
 * SabreDAV:					https://example.com/addressbooks/{resource|principal|username}/{collection}/
 * ownCloud:					https://example.com/apps/contacts/carddav.php/addressbooks/{resource|principal|username}/{collection}/
 * SOGo:						https://example.com/SOGo/dav/{resource|principal|username}/Contacts/{collection}/
 *
 *
 * @author Christian Putzke <christian.putzke@graviox.de>
 * @copyright Christian Putzke
 * @link http://www.graviox.de/
 * @link https://twitter.com/cputzke/
 * @since 20.07.2011
 * @version 0.6
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 */
class Client {

	/**
	 * CardDAV PHP Version
	 *
	 * @constant	string
	 */
	const VERSION = '0.6';

	/**
	 * User agent displayed in http requests
	 *
	 * @constant	string
	 */
	const USERAGENT = 'IFW CardDAV Client';

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
	 * Characters used for vCard id generation
	 *
	 * @var	array
	 */
	private $vcardIdChars = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C', 'D', 'E', 'F');

	/**
	 * CardDAV server connection (curl handle)
	 *
	 * @var	resource
	 */
	private $curl;

	/**
	 * Debug on or off
	 *
	 * @var	boolean
	 */
	private $debug = false;

	/**
	 * All available debug information
	 *
	 * @var	array
	 */
	private $debugInfo = array();

	/**
	 * Exception codes
	 */
	const EXCEPTION_WRONG_HTTP_STATUS_CODE_GET = 1000;
	const EXCEPTION_WRONG_HTTP_STATUS_CODE_GET_VCARD = 1001;
	const EXCEPTION_WRONG_HTTP_STATUS_CODE_GET_XML_VCARD = 1002;
	const EXCEPTION_WRONG_HTTP_STATUS_CODE_DELETE = 1003;
	const EXCEPTION_WRONG_HTTP_STATUS_CODE_ADD = 1004;
	const EXCEPTION_WRONG_HTTP_STATUS_CODE_UPDATE = 1005;
	const EXCEPTION_MALFORMED_XML_RESPONSE = 1006;
	const EXCEPTION_COULD_NOT_GENERATE_NEW_VCARD_ID = 1007;

	/**
	 * Constructor
	 * Sets the CardDAV server url
	 *
	 * @param	string	$host	CardDAV server url
	 */
	public function __construct($host ) {
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


	
	public function propFind($path, $properties, $depth = "0") {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElementNS('DAV:', 'd:propfind');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cs', 'http://calendarserver.org/ns/');

		$prop = $dom->createElement('d:prop');

		foreach ($properties as $property) {

				list(
						$namespace,
						$elementName
				) = \Sabre\Xml\Service::parseClarkNotation($property);

				if ($namespace === 'DAV:') {
						$element = $dom->createElement('d:' . $elementName);
				} else if($namespace === 'cs:') {
					$element = $dom->createElement('cs:' . $elementName);
				} else {						
					 $element = $dom->createElementNS($namespace, 'x:' . $elementName);
				}

				$prop->appendChild($element);
		}

		$dom->appendChild($root)->appendChild($prop);

		$body = $dom->saveXML();
		
		
		
		return $this->request($path, 'PROPFIND', $body, [
				'Content-Type: application/xml',
				'Depth: '.$depth,
				'Prefer: return-minimal'
		]);
	}
	
	
	/**
	 * 
	 * @param type $path
	 * @param type $properties
	 * @param type $depth
	 * @return Response
	 */
	public function report($path, $properties, $depth = "0") {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElement('card:addressbook-query');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d', 'DAV:');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cs', 'http://calendarserver.org/ns/');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:card', 'urn:ietf:params:xml:ns:carddav');

		$prop = $dom->createElement('d:prop');

		foreach ($properties as $property) {

				list(
						$namespace,
						$elementName
				) = \Sabre\Xml\Service::parseClarkNotation($property);

				if ($namespace === 'DAV:') {
						$element = $dom->createElement('d:' . $elementName);
				} else if($namespace === 'cs:') {
					$element = $dom->createElement('cs:' . $elementName);
				} else if($namespace === 'card:') {
					$element = $dom->createElement('card:' . $elementName);
				} else {		
					 $element = $dom->createElementNS($namespace, 'x:' . $elementName);
				}

				$prop->appendChild($element);
		}

		$dom->appendChild($root)->appendChild($prop);

		$body = $dom->saveXML();
		
		
		
		return $this->request($path, 'REPORT', $body, [
				'Content-Type: application/xml',
				'Depth: '.$depth,
				'Prefer: return-minimal'
		]);
	}
	
	/**
	 * REPORT /addressbooks/johndoe/contacts/ HTTP/1.1
Depth: 1
Content-Type: application/xml; charset=utf-8

<card:addressbook-multiget xmlns:d="DAV:" xmlns:card="urn:ietf:params:xml:ns:carddav">
    <d:prop>
        <d:getetag />
        <c:addressbook-data />
    </d:prop>
    <d:href>/addressbooks/johndoe/contacts/abc-def-fez-123454657.vcf</d:href>
    <d:href>/addressbooks/johndoe/contacts/acme-12345.vcf</d:href>
</card:addressbook-multiget>
	 */
	public function multiget($path, $properties = [] , $uris = []) {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElement('card:addressbook-multiget');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d', 'DAV:');
//		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cs', 'http://calendarserver.org/ns/');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:card', 'urn:ietf:params:xml:ns:carddav');
		
		$dom->appendChild($root);
						
		$prop = $dom->createElement('d:prop');

		foreach ($properties as $property) {
			$element = $dom->createElement($property);				
			$prop->appendChild($element);
		}
		
		$root->appendChild($prop);
		
		foreach($uris as $uri) {
			$element = $dom->createElement('d:href', $uri);				
			$root->appendChild($element);
		}

		
		$body = $dom->saveXML();
		
		return $this->request($path, 'REPORT', $body, [
				'Content-Type: application/xml',
				'Depth: 1'
		]);
	}




	/**
	 * Checks if the CardDAV server is reachable
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
	
	
	public function put($uri, $vcard, $etag = null) {
		
		$headers = [
				'Content-Type: text/vcard; charset=UTF-8'				
		];
		
		if(isset($etag)) {
			$headers[] = 'If-Match: '.$etag;
		}
		
		return $this->request($uri, 'PUT', $vcard, $headers);
	}
	
	public function delete($uri, $etag = null) {
		$headers = [];
		
		if(isset($etag)) {
			$headers[] = 'If-Match: '.$etag;
		}
		
		return $this->request($uri, 'DELETE', null, $headers);
	}

	/**
	 * Query the CardDAV server via curl and returns the response
	 *
	 * @param	string	$path				Path on the server
	 * @param	string	$method				HTTP method like (OPTIONS, GET, HEAD, POST, PUT, DELETE, TRACE, COPY, MOVE)
	 * @param	string	$body			Content for CardDAV queries
	 * 
	 * @return	array						Raw CardDAV Response and http status code
	 */
	private function request($path, $method, $body = null, $headers = []) {
		$this->curlInit();

		curl_setopt($this->curl, CURLOPT_URL, $this->host.$path);
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
		$response = substr($completeResponse, $header_size);
		
		$completeRequest =  $requestHeaders."\n\n".$body;
		
		\IFW::app()->debug($completeRequest, 'dav');
		\IFW::app()->debug($completeResponse, 'dav');
		
		return new Response($status, $response, $responseHeaders);
		

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
