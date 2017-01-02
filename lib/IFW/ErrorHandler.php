<?php

namespace IFW;

use ErrorException;
use Exception;
use IFW;
/**
 * Error handler class
 * 
 * Handles's all errors and exceptions
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ErrorHandler {

	public function __construct() {
		error_reporting(E_ALL | E_STRICT);
		ini_set('display_errors', 'off');
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
	 * PHP7 has new throwable interface. We can' use type hinting if we want to support php 5.6 as well.
	 * @param Throwable $e
	 */
	public function exceptionHandler($e) {

		if(PHP_SAPI == 'cli') {
			echo "[".date(IFW\Util\DateTime::FORMAT_API)."] ERROR: ". (string) $e."\n\n";
		}else
		{		
			IFW::app()->debug($e->getMessage());
			IFW::app()->debug($e->getTraceAsString());
			
			$view = new IFW\View\Web\Exception();
			\IFW::app()->getResponse()->send($view->render($e));		
		}
	}

//	/**
//	 * Custom error handler that logs to our own error log
//	 * 
//	 * @param int $errno
//	 * @param string $errstr
//	 * @param string $errfile
//	 * @param int $errline
//	 * @return boolean
//	 */
//	public function printError($errno, $errstr, $errfile, $errline) {
//
//		$type = "Unknown error ($errno)";
//
//		switch ($errno) {
//			case E_CORE_ERROR:
//			case E_COMPILE_ERROR:
//			case E_ERROR:
//			case E_USER_ERROR:
//				$type = 'Fatal error';
//				break;
//
//			case E_WARNING:
//			case E_USER_WARNING:
//				$type = 'Warning';
//				break;
//
//			case E_NOTICE:
//			case E_USER_NOTICE:
//				$type = 'Notice';
//				break;
//		}
//
//		$errorMsg = "[" . date("Ymd H:i:s") . "] PHP $type: $errstr in $errfile on line $errline";
//
////		$user = \IFW\$this->auth()->user() ? \IFW\$this->auth()->user()->username : 'notloggedin';
//		$user = "none";
//
//		$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
//		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
//
//		$errorMsg .= "\nUser: " . $user . " Agent: " . $agent . " IP: " . $ip . "\n";
//
//		if (isset($_SERVER['QUERY_STRING']))
//			$errorMsg .= "Query: " . $_SERVER['QUERY_STRING'] . "\n";
//
//
//		$backtrace = debug_backtrace();
//		array_shift($backtrace); //first item is this function which we don't have to see
//
//		$errorMsg .= "Backtrace:\n";
//		foreach ($backtrace as $o) {
//
//			if (!isset($o['class']))
//				$o['class'] = 'global';
//
//			if (!isset($o['function']))
//				$o['function'] = 'global';
//
//			if (!isset($o['file']))
//				$o['file'] = 'unknown';
//
//			if (!isset($o['line']))
//				$o['line'] = 'unknown';
//
//			$errorMsg .= $o['class'] . '::' . $o['function'] . ' in file ' . $o['file'] . ' on line ' . $o['line'] . "\n";
//	}
//		$errorMsg .= "----------------";
//
//		echo $errorMsg;
//	}

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

		/* Execute PHP internal error handler too */
//		return false;
	}

}
