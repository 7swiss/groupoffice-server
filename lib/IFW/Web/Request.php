<?php
namespace IFW\Web;

use Exception;
use IFW\Data\Object;
use IFW\Web\Encoder\JsonEncoder;
use IFW\Web\Encoder\XmlEncoder;

/**
 * The HTTP request class.
 *
 * <p>Example:</p>
 * ```````````````````````````````````````````````````````````````````````````
 * $var = IFW::app()->request()->queryParams['someVar'];
 * 
 * //Get the JSON or XML data
 * $var = IFW::app()->request()->payload['somevar'];
 * ```````````````````````````````````````````````````````````````````````````
 *
 * @property-read string[] $queryParams {@see getQueryParams()}
 * @property-read string[] $body {@see getPayload()}
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Request extends Object{

	/**
	 * The body of the request. Only JSON is supported at the moment.
	 * 
	 * @var mixed[]
	 */
	private $body;	
	
	private $rawBody;
	
	/**
	 * The request headers
	 * 
	 * @var string[] 
	 */
	private $headers;
	
	/**
	 * Get all query parameters of this request
	 * 
	 * @return array ['paramName' => 'value']
	 */
	public function getQueryParams() {
		return $_GET;
	}
	
	/**
	 * Get the route for the router
	 * 
	 * This is the path between index.php and the query parameters with trailing and leading slashes trimmed.
	 * 
	 * In this example:
	 * 
	 * /index.php/some/route?queryParam=value
	 * 
	 * The route would be "some/route"
	 * 
	 * @param string|null
	 */
	public function getRoute() {
		return isset($_SERVER['PATH_INFO']) ? ltrim($_SERVER['PATH_INFO'], '/') : null;
	}
	
	private function createEncoder() {
		
		if ($this->isXml()) {
			return new XmlEncoder();
		}
		
		if($this->isJson()){		
			return new JsonEncoder();	
		}
		
		throw new Exception("Unsupported request payload with content type: ".$this->getContentType());
	}
	
	/**
	 * Get the values of the Accept header
	 * 
	 * @param string[]
	 */
	public function getAccept() {

		if(empty($_SERVER['HTTP_ACCEPT'])) {
			return [];
		}
		
		$accept = explode(',', $_SERVER['HTTP_ACCEPT']);		
		$accept = array_map('trim', $accept);		
		$accept = array_map('strtolower', $accept);
		
		return $accept;
	}

	/**
	 * Get the request headers as a key value array. The header names are in lower case.
	 * 
	 * Example:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * [
	 * 'accept' => 'application/json',
	 * 'accept-aanguage' => 'en-us'
	 * ]
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @return array
	 */
	public function getHeaders() {		
		
		if(!function_exists('apache_request_headers'))
		{
			return [];
		}
		
		if (!isset($this->headers)) {
			$this->headers = array_change_key_case(apache_request_headers(),CASE_LOWER);			
		}
		return $this->headers;
	}
	
	/**
	 * Get request header value
	 * 
	 * @param string $name
	 * @param string
	 */
	public function getHeader($name) {
		$name = strtolower($name);
		$headers = $this->getHeaders();
		return isset($headers[$name]) ? $headers[$name] : null;
	}

	/**
	 * Get the request payload
	 * 
	 * The data send in the body of the request. 
	 * We support:
	 * 
	 * #application/x-www-form-urlencoded
	 * #multipart/form-data
	 * #application/json
	 * #application/xml or text/xml
	 * 
	 * @param string
	 */
	public function getBody() {
		if (!isset($this->body)) {			
			//If it's a form post (application/x-www-form-urlencoded or multipart/form-data) with HTML then PHP already built the data
			if(!empty($_POST)) {
				$this->body = $_POST;
				
//				Might be bad for performance to do.
//				if(isset($_FILES)) {
//					$this->payload = array_merge($this->payload, $_FILES);
//				}
				
			}else
			{				
				$encoder = $this->createEncoder();
				$this->body = $encoder->decode($this->getRawBody());
			}
		}

		return $this->body;
	}
	
	/**
	 * Get raw request body as string.
	 * @param string
	 */
	public function getRawBody() {
		if(!isset($this->rawBody)) {
			$this->rawBody = file_get_contents('php://input');
		}
		
		return $this->rawBody;
	}

	/**
	 * Get's the content type header
	 *
	 * @param string
	 */
	public function getContentType() {
		return isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : '';
	}

	/**
	 * Get the request method
	 * 
	 * @param string PUT, POST, DELETE, GET, PATCH, HEAD
	 */
	public function getMethod() {
		return strtoupper($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Check if the request posted a JSON body
	 *
	 * @return boolean
	 */
	private function isJson() {
		return strpos($this->getContentType(), 'application/json') !== false;
	}
	
	/**
	 * Check if the request posted a JSON body
	 *
	 * @return boolean
	 */
	private function isXml() {
		return strpos($this->getContentType(), '/xml') !== false;
	}

	/**
	 * Check if this request SSL secured
	 *
	 * @return boolean
	 */
	public function isHttps() {
		return !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off');
	}
}