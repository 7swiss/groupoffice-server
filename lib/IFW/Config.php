<?php
namespace IFW;

use IFW;
use IFW\Fs\Folder;
use IFW\Data\Object;

/**
 * Config class with all configuration options. 
 * 
 * It can configure all objects that extend the AbstractObject class.
 * 
 * You can also set any value to it and it will be stored in the database.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Config {
	
	/**
	 * API version number
	 */
	const VERSION = "7.0";
	
	/**
	 * Name of the application
	 *
	 * @var string
	 */
	public $productName = 'Group-Office 7.0';
	
	
	/**
	 * The class that handles caching
	 * 
	 * @var string 
	 */
	public $cacheClass = "\\IFW\\Cache\\Disk";

	/**
	 * Temporary files folder to use. Defaults to the <system temp folder>/ifw
	 *
	 * @var string
	 */
	private $tempFolder;

	/**
	 * Data folder to store permanent files. Defaults to "/home/ifw"
	 *
	 * @var string
	 */
	private $dataFolder = '/home/ifw';
	
	/**
	 * Configuration for all objects that extend AbstractObject
	 *
	 * @see Object
	 * @var array
	 */
	public $classConfig = [];
	
	
	/**
	 * Set's class config options.
	 *
	 * More information in IFW::app()->init();
	 *
	 * @see IFW::app()->init()
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->classConfig = $config;

		$className = get_class($this);

		if (isset($this->classConfig[$className])) {
			foreach ($this->classConfig[$className] as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Get path to lib/IFW
	 *
	 * @param string
	 */
	public function getLibPath() {
		return realpath(dirname(__FILE__) . '/../');
	}

	/**
	 * Get temporary files folder
	 * 
	 * The folder of this library and the PHP_SAPI name will automatically be 
	 * appended to ensure that this folder is private to this environment.
	 *
	 * @return Folder
	 */
	public function getTempFolder($autoCreate = true) {

		if (!isset($this->tempFolder)) {
			$this->tempFolder = sys_get_temp_dir() . '/groupoffice' . dirname($this->getLibPath());
		}
		
		$path = $this->tempFolder;
		
		$path .= '/'.PHP_SAPI;
		
		$folder = new Folder($path);
		if ($autoCreate) {
			$old = umask(0); //world reabable
			$folder->create();
			umask($old);
		}		

		return $folder;
	}

	/**
	 * Get temporary files folder
	 *
	 * @return Folder
	 */
	public function getDataFolder() {
		return new Folder($this->dataFolder);
	}
}