<?php
namespace IFW\View\Cli;

use Exception;
use IFW;
use IFW\Web\Encoder\XmlEncoder;
use IFW\View\ViewInterface;

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
	
	
	/**
	 * Renders the JSON or XML
	 * 
	 * @param array $data
	 * @return self
	 */
	public function render($data) {		
		
//		$encoder = new IFW\Web\Encoder\PlainEncoder();
				
		if (isset($data['debug'])) {
			throw new Exception('debug is a reserved data object');
		}

		if (!isset($data['success'])) {
			$data['success'] = true;
		}
		
		if(IFW::app()->getDebugger()->enabled) {		
			
			IFW::app()->getDebugger()->debug("Peak memory usage: ".memory_get_peak_usage());
			
			$data['debug'] = IFW::app()->getDebugger()->entries;
		}		
		
		$this->data = $data;
						
		return $this;
	}
	
	public function __toString() {
		$encoder = new IFW\Web\Encoder\PlainEncoder();
		return $encoder->encode($this->data);
	}
}