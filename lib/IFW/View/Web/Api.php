<?php
namespace IFW\View\Web;

use Exception;
use IFW;
use IFW\View\ViewInterface;
use IFW\Web\Encoder\XmlEncoder;

/**
 * The default API view
 * 
 * Views are used in {@see Controller}.
 * Renders JSON or XML based on the HTTP Accept header the client sent.
 * 
 * Example:
 * 
 * <code>  
 * $data = ['success' => true];
 * 
 * $view = new Api();		
 * $output = $view->render($data);	
 * </code>
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Api implements ViewInterface {
	
	private $data;
	
	private $encoder;
	
	/**
	 * Get the content encoder
	 * 
	 * @return XmlEncoder|\IFW\Web\Encoder\JsonEncoder
	 */
	private function getEncoder() {
		
		if(!isset($this->encoder)) {
			
			if(IFW::app() instanceof \IFW\Cli\App) {
				$this->encoder = new IFW\Web\Encoder\PlainEncoder();
			}else
			{
		
				$accepts = IFW::app()->getRequest()->getAccept();	

				$accepts[] =  IFW::app()->getRequest()->getContentType();

				foreach($accepts as $accept) {
					switch($accept) {				
						case '*/*':
						case 'application/json':
								$this->encoder = new IFW\Web\Encoder\JsonEncoder();		
							break 2;
						case 'application/xml':
						case 'text/xml':
							$this->encoder = new XmlEncoder();
							break 2;
					}
				}
				
				if(!isset($this->encoder)) {
					IFW::app()->debug("Couldn't find any supported Accept encoding in client request header (Accept: ".implode($accepts,',')."). Falling back on application/json");
					$this->encoder = new IFW\Web\Encoder\JsonEncoder();					
				}
			}

			IFW::app()->getResponse()->setContentType($this->encoder->getContentType());	
		}
		
		return $this->encoder;
	}
	
	/**
	 * Renders the JSON or XML
	 * 
	 * @param array $data
	 * @return self
	 */
	public function render($data) {	
			
		if (isset($data['debug'])) {
			throw new Exception('debug is a reserved data object');
		}

		if (!isset($data['success'])) {
			$data['success'] = true;
		}
		
		
		//just for debugging entries. Otherwise they are called after rendering
		$this->getEncoder();
		
		if(IFW::app()->getDebugger()->enabled) {		
			
			IFW::app()->getDebugger()->debug("Peak memory usage: ".memory_get_peak_usage());
			
			$data['debug'] = IFW::app()->getDebugger()->entries;
		}		
		
		$this->data = $data;
		
		return $this;
		
	}	
	
	public function __toString() {		
		return $this->getEncoder()->encode($this->data);	
	}
	
	
	
}