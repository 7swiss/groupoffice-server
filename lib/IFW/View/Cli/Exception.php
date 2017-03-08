<?php
namespace IFW\View\Cli;

use IFW\View\ViewInterface;

/**
 * The default exception view
 * 
 * This view is rendered by {@see IFW\ErrorHandler}
 * Renders JSON or XML based on the HTTP Accept header the client sent.
 * 
 * Example:
 * 
 * ```````````````````````````````````````````````````````````````````````````  
 * $data = ['success' => true];
 * 
 * $view = new Api();		
 * $output = $view->render($data);	
 * ```````````````````````````````````````````````````````````````````````````
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Exception extends Api implements ViewInterface {
	
	/**
	 * Renders the JSON or XML
	 * 
	 * @param \Exception $exception
	 * @return self
	 */
	public function render($exception) {
		
		$data = [];
		$data['success'] = false;
		$data['errors'][] = $exception->getMessage();
		$data['exception'] = explode("\n", (string) $exception);
		
		
		return parent::render($data);		
	}
}