<?php

namespace GO\Core\Install\Model;

use IFW\Data\Model;
use IFW;
use PDOException;

/**
 * System check result model
 * 
 * It contains a boolean and feedback.
 */
class SystemCheckResult extends Model {

	public $success;
	public $msg;

	public function __construct($success, $msg = "OK") {

		parent::__construct();

		$this->success = $success;
		$this->msg = $msg;
	}

}
/**
 * System check model to test if the system meets the system requirements
 */
class SystemCheck extends Model {

	private $_checks = [];

	public function __construct() {
		parent::__construct();

		$this->_registerCheck(
						"PHP version", function() {
			$version = phpversion();
			
			$requiredVersion = "5.6";
			
			if (version_compare($version, $requiredVersion, ">=")) {
				return new SystemCheckResult(true, "OK (" . $version . ")");
			} else {

				return new SystemCheckResult(false, "Your PHP version '$version' is older then the minimum required version '$requiredVersion'");
			}
		});
		
		$this->_registerCheck("Mcrypt extension", function(){			
			if(function_exists("mcrypt_create_iv")) {
				return new SystemCheckResult(true);
			}else
			{
				return new SystemCheckResult(false, "Required, but not installed");
			}
		});

		$this->_registerCheck(
						"Database connection", function() {
			try {
				$conn = GO()->getDbConnection()->getPDO();
				return new SystemCheckResult(true, "Connection established");
			} catch (PDOException $e) {
				return new SystemCheckResult(false, "Couldn't connect to database. Please check the config. PDO Exception: " . $e->getMessage());
			}
		});

		$this->_registerCheck(
						"Temp folder", function() {
			$file = GO()->getConfig()->getTempFolder()->getFile("test.txt");

			if ($file->touch()) {

				$file->delete();

				return new SystemCheckResult(true, 'Is writable');
			} else {
				return new SystemCheckResult(false, "'" . GO()->getConfig()->getTempFolder() . "' is not writable!");
			}
		});

		$this->_registerCheck(
						"Data folder", function() {

			$folder = GO()->getConfig()->getDataFolder();

			if (!$folder->exists()) {
				return new SystemCheckResult(false, '"' . $folder . '" doesn\'t exist');
			}

			$file = $folder->getFile("test.txt");

			if ($file->touch()) {

				$file->delete();

				return new SystemCheckResult(true, "Is writable");
			} else {
				return new SystemCheckResult(false, "'" . $folder . "' is not writable!");
			}
		});
	}

	private function _registerCheck($name, $function) {
		$this->_checks[$name] = $function;
	}
	
	/**
	 * Run all system checks
	 * 
	 * It will return an array with a success boolean and array of system check results.
	 * @return array
	 */
	public function run(){
		
		$response = [
			'success' => true, 
			'databaseInstalled' => System::isDatabaseInstalled(),
			'cacheFlushed' => GO()->getCache()->flush(),
			'installUrl' => (string) GO()->getRouter()->buildUrl('/system/install'),
			'upgradeUrl' => (string) GO()->getRouter()->buildUrl('/system/upgrade'),
			'checks' => []
			];
		
		foreach($this->_checks as $name => $function){
			
			$result = $function();
			
			/* @var $result SystemCheckResult */			
			if(!$result->success){
				$response['success'] = false;
			}
			
			$response['checks'][$name] = $result->toArray();
		}
		
		return $response;		
	}
}