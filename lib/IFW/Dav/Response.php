<?php
namespace IFW\Dav;

class Response {
	
	public $status;
	public $body;
	public $headers;
	
	public function __construct($status, $body, $headers) {
		$this->status = $status;
		$this->body = $body;
		$this->headers = $headers;
		
	}
	
	/**
	 * 
	 * @return \SimpleXMLElement
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