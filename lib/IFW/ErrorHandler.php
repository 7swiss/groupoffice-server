<?php

namespace IFW;

use ErrorException;
use IFW;
/**
 * Error handler class
 * 
  * All PHP errors will be converted into ErrorExceptions. If they are not caught
 * by the developers code then they will be handled by {@see exceptionHandler()}
 * It will render the error and log it to the system log using error_log 
 * regardless of the php.ini settings.
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ErrorHandler {

	public function __construct() {
		error_reporting(E_ALL | E_STRICT);
		
		//doesn't matter because we're catching all errors and handle the display ourselves
		//ini_set('display_errors', 'off');
		
		set_error_handler([$this, 'errorHandler']);
		register_shutdown_function([$this, 'shutdown']);
		set_exception_handler([$this, 'exceptionHandler']);
	}

	/**
	 * Called when PHP exits.
	 */
	public function shutdown() {

		$error = error_get_last();
		if ($error) {
			//Log only fatal errors because other errors should have been logged by the normal error handler
			if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
//				$this->printError($error['type'], $error['message'], $error['file'], $error['line']);				
				
				$this->exceptionHandler(new \ErrorException($error['message'],0,$error['type'],$error['file'], $error['line']));
			}
		}

//		$this->debug("shutdown");
	}

	/**
	 * PHP7 has new throwable interface. We can' use type hinting if we want to 
	 * support php 5.6 as well.
	 * @param Throwable $e
	 */
	public function exceptionHandler($e) {		
		$cls = get_class($e);
		
		$errorString = $cls.': ' . $e->getMessage()." in " . $e->getFile() ." on line ". $e->getLine();
		error_log($errorString, 0);

		if(PHP_SAPI == 'cli') {
			echo "[".date(IFW\Util\DateTime::FORMAT_API)."] ". $errorString."\n\n";
		}else
		{		
			IFW::app()->debug($errorString);
			foreach(explode("\n", (string) $e) as $line) {
				IFW::app()->debug($line);
			}
			
			$view = new IFW\View\Web\Exception();
			\IFW::app()->getResponse()->send($view->render($e));		
		}
	}

	/**
	 * Custom error handler that logs to our own error log
	 * 
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @return boolean
	 */
	public static function errorHandler($errno, $errstr, $errfile, $errline) {
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
}