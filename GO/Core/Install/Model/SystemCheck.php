<?php

namespace GO\Core\Install\Model;

use IFW;
use IFW\Data\Model;
use PDOException;
use function GO;

/**
 * System check model to test if the system meets the system requirements
 */
class SystemCheck extends Model {

	private $checks = [];
	
	private $requiredExtensions = [
			'mbstring', 
			'zip', 
			'pcre', 
			'date', 
			'iconv', 
			'pdo', 
			'gd', 
			'ctype', 
			'curl',
			'openssl'
			];

	public function __construct() {
		parent::__construct();
		
		$this->checks[] = new Check("PHP version", function($check) {
			
			$version = phpversion();

			$requiredVersion = "5.6";

			$check->success = version_compare($version, $requiredVersion, ">=");
			$check->message = $check->success ? "OK" : "Your PHP version '$version' is older then the minimum required version '$requiredVersion'";
		});
		
		$this->checks[] = new Check("Memory limit", function($check) {			
			$check->success = IFW::app()->getEnvironment()->getMemoryLimit() >= 128 * 1024 * 1024; 
			$check->message = $check->success ? "OK" : "The php 'memory_limit' settings is low (".(IFW::app()->getEnvironment()->getMemoryLimit() / 1024 / 1024)." MB). Please set to a minimum of 128 MB";
		});
		

		


		$this->checks[] = new Check("Database connection", function($check) {
			try {
				$conn = GO()->getDbConnection()->getPDO();								
			} catch (PDOException $e) {
				$check->message = "Couldn't connect to database. Please check the config. PDO Exception: " . $e->getMessage();
				$check->success = false;
			}
		});

		$this->checks[] = new Check("Temp folder", function($check) {
			$file = GO()->getConfig()->getTempFolder()->getFile("test.txt");

			if ($file->touch()) {

				$file->delete();

				
			} else {
				$check->success = false;
				$check->message = "'" . \IFW\Config::class. "::tempFolder' is not writable!";
			}
		});

		$this->checks[] = new Check("Data folder", function($check) {

			$folder = GO()->getConfig()->getDataFolder();

			if (!$folder->exists()) {
				$check->success = false;
				$check->message = '"' . \IFW\Config::class . '::dataFolder" doesn\'t exist';
				return;
			}

			$file = $folder->getFile("test.txt");

			if ($file->touch()) {

				$file->delete();

			} else {
				$check->success = false;
				$check->message = '"' . \IFW\Config::class . '::dataFolder" is not writable';
			}
		});
		
		
		$this->checkExtensions();
		
		
		
	}
	
	private function checkExtensions() {
		
		foreach($this->requiredExtensions as $extension) {
			$this->checks[] = new Check("PHP Extension '".$extension."'", function($check) use ($extension) {
				
				$check->success = extension_loaded($extension);
				if(!$check->success) {
					$check->message =  "PHP extension '$extension' is required but not available on your system";
				}
			});
		}
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
		
		foreach($this->checks as $check){
			$check->run();
			if(!$check->success) {
				$response['success'] = false;
			}
			$response['checks'][] = $check->toArray();
		}
		
		return $response;		
	}
}