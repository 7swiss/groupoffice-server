<?php
namespace IFW\Dav;

use Iterator;
use XMLReader;

class MultiResponse implements Iterator{
	/**
	 *
	 * @var XMLReader
	 */
	private $reader;
	private $xml;
	
	private $current;
	
	
	public function __construct($xml) {
		$this->xml = $xml;
	}
	
	public function current() {
		return $this->current;
	}

	public function key() {
		return null;
	}

	public function next() {
		$this->current = null;
		while($this->reader->read()) {
			if("d:response" === $this->reader->name && $this->reader->nodeType == XMLReader::ELEMENT) {		
				
				$responseXML = $this->reader->readOuterXml();

				$this->current = simplexml_load_string($responseXML);
				$this->current->registerXPathNamespace("card", "urn:ietf:params:xml:ns:carddav");
				$this->current->registerXPathNamespace("cs", "http://calendarserver.org/ns/");
				$this->current->registerXPathNamespace("d", "DAV:");				
				
				return;
			}
		}
	}

	public function rewind() {	
		
			//Use XMLReader to split responses as it's better for memory
		$this->reader = new XMLReader();	
		$this->reader->XML($this->xml);
		$this->next();
	}

	public function valid() {
		return isset($this->current);
	}
}