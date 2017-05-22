<?php

namespace IFW\Util;

/**
 * Server information class.
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Environment {

	/**
	 * Check if this is a windows server
	 * 
	 * @return boolean
	 */
	public function isWindows() {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}
	
	/**
	 * Check if we are ran with the Command Line Interface
	 * 
	 * @return boolean
	 */
	public function isCli() {
		return PHP_SAPI === 'cli';
	}

	/**
	 * Get the hostname of this machine
	 * 
	 * @param string
	 */
	public function getHostname() {
		if (isset($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
		} else {
			return php_uname('n');
		}
	}

	/**
	 * Get PHP memory limit in bytes
	 * 
	 * @return int
	 */
	public function getMemoryLimit() {
		return $this->configToBytes(ini_get('memory_limit'));
	}
	
	/**
	 * Get the maximum size of a file upload
	 * 
	 * To increase increase in php.ini:
	 * 
	 * 1. memory_limit
	 * 2. post_max_size
	 * 3. upload_max_filesize
	 * 
	 * The smallest value will apply here.
	 * 
	 * @return int
	 */
	public function getMaxUploadSize() {
		return min($this->configToBytes(ini_get('post_max_size')), $this->configToBytes(ini_get('upload_max_filesize')), $this->configToBytes(ini_get('memory_limit')));
	}

	/**
	 * Converts shorthand memory notation value to bytes
	 * From http://php.net/manual/en/function.ini-get.php
	 *
	 * @param $val Memory size shorthand notation string
	 */
	private function configToBytes($val) {
		$val = trim($val);
		$last = strtolower(substr($val,-1));
		$val = substr($val, 0, -1);
		switch ($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'P':  
        $val *= 1024;  
			case 'T':  
				$val *= 1024;
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

}
