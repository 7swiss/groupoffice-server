<?php
namespace IFW\View\Web;

use IFW;
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
	 * @param Exception $data
	 * @return self
	 */
	public function render($exception) {
		
		$data = [];
		
		$data['date'] = date(IFW\Util\DateTime::FORMAT_API);
		
		$data['success'] = false;
		
		$cls = get_class($exception);
		
		$errorString = $cls.': ' . $exception->getMessage()." in " . $exception->getFile() .": ". $exception->getLine();
		
		$data['errors'][] = $errorString;
//		$data['exception'] = explode("\n", (string) $exception);
		
		$httpStatus = $exception instanceof IFW\Exception\HttpException ? $exception->getCode() : 500;
		
		if($exception instanceof IFW\Exception\Forbidden) {
			$httpStatus = 403;
		} elseif($exception instanceof \IFW\Auth\Exception\LoginRequired || $exception instanceof \IFW\Auth\Exception\BadLogin) {
			$httpStatus = 401;
		}

		IFW::app()->getResponse()->setStatus($httpStatus);
		
		return parent::render($data);		
	}
}