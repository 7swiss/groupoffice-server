<?php
namespace IFW\Http;

class Response {
	
	public $status;
	public $body;
	public $headers;
	
	public function __construct($status, $body, $headers) {
		$this->status = $status;
		$this->body = $body;
		$this->headers = $headers;		
	}
}