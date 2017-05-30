<?php
namespace IFW\Dav;

use IFW\Http\Response as HttpResponse;
use SimpleXMLElement;

class Response extends HttpResponse {
		
	/**
	 * 
	 * @return SimpleXMLElement
	 */
	public function getBodyAsXml() {
		$xml = simplexml_load_string($this->body);
		$xml->registerXPathNamespace("card", "urn:ietf:params:xml:ns:carddav");
		$xml->registerXPathNamespace("cs", "http://calendarserver.org/ns/");
		$xml->registerXPathNamespace("d", "DAV:");
		
		
		
		return $xml;
	}
	
	public function getMultiResponse() {
		return new MultiResponse($this->body);
	}
}