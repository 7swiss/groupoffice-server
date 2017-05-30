<?php

namespace IFW\Dav;

use DOMDocument;
use IFW\Http\Client as HttpClient;
use Sabre\Xml\Service;
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
class Client extends HttpClient {

	
	public function propFind($path, $properties, $depth = "0") {
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElementNS('DAV:', 'd:propfind');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cs', 'http://calendarserver.org/ns/');

		$prop = $dom->createElement('d:prop');

		foreach ($properties as $property) {

				list(
						$namespace,
						$elementName
				) = Service::parseClarkNotation($property);

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
		$dom = new DOMDocument('1.0', 'UTF-8');
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
				) = Service::parseClarkNotation($property);

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
		$dom = new DOMDocument('1.0', 'UTF-8');
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

	protected function createResponse($status, $responseText, $responseHeaders) {
		return new Response($status, $responseText, $responseHeaders);
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
}