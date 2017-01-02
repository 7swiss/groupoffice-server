<?php
namespace IFW\Web;

use IFW\App as BaseApp;
use IFW\Web\Request;
use IFW\Web\Response;
use IFW\Web\Router;

abstract class App extends BaseApp {
	
	/**
	 *
	 * @var Request 
	 */
	protected $request;
	
	/**
	 *
	 * @var Response 
	 */
	protected $response;
	
	
	/**
	 * Get the HTTP Request object
	 * 
	 * @return Request
	 */
	public function getRequest() {
		
		if(!isset($this->request)) {
			$this->request = new Request();
		}		
		return $this->request;
	}
	
	/**
	 * Get the HTTP Response object
	 * 
	 * @return Response
	 */
	public function getResponse() {
		if(!isset($this->response)) {
			$this->response = new Response();
		}		
		return $this->response;
	}
	
	/**
	 * Get the application router
	 * @return Router
	 */
	public function getRouter() {
		if(!isset($this->router)) {
			$this->router = new Router();
		}		
		return $this->router;
	}
	
	/**
	 * 
	 * {@inheritdoc}
	 */
	public function getDebugger() {
		
		$debugger = parent::getDebugger();
		if(!empty($this->getRequest()->getHeader('X-Debug'))){
			$debugger->enabled = true;	
		}
		
		return $debugger;
	}
}
