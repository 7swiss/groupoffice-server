<?php

namespace IFW\Cache;

use IFW;
use IFW\Cache\CacheInterface;
use IFW\Fs\File;

/**
 * Cache implementation that uses serialized objects in files on disk.
 * The cache is persistent accross requests.
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Disk implements CacheInterface {


	private $folder;
	
	private $cache;

	public function __construct() {
		$this->folder = IFW::app()->getConfig()->getDataFolder()->getFolder('diskcache');
//		if(!$this->folder->exists()) {
//			$this->folder->create();
//			$this->folder->chmod(0777);
//		}
	}

	

	/**
	 * Store any value in the cache
	 * 
	 * @param string $key
	 * @param mixed $value Will be serialized
	 * @param boolean $persist Cache must be available in next requests. Use false of it's just for this script run.
	 */
	public function set($key, $value, $persist = true) {

		//don't set false values because unserialize returns false on failure.
		if ($key === false)
			return true;

		$key = File::stripInvalidChars($key, '-');		
		if($persist) {
			$file = $this->folder->getFile($key);
			$file->putContents(serialize($value));
		}
		
		$this->cache[$key] = $value;
	}

	/**
	 * Get a value from the cache
	 * 
	 * @param string $key 
	 * @return mixed null if it doesn't exist
	 */
	public function get($key) {

		$key = File::stripInvalidChars($key, '-');
		
		if(isset($this->cache[$key])) {
			return $this->cache[$key];
		}
		
		$file = $this->folder->getFile($key);

		if (!$file->exists()) {
			return null;
		} 

		$this->cache[$key] = unserialize($file->getContents());

		if ($this->cache[$key] === false) {
			trigger_error("Could not unserialize cache from file " . $key);
			$this->delete($key);
			return null;
		} else {
			return $this->cache[$key];
		}		
	}

	/**
	 * Delete a value from the cache
	 * 
	 * @param string $key 
	 */
	public function delete($key) {
		$key = File::stripInvalidChars($key, '-');
		
		unset($this->cache[$key]);
		
		if (file_exists($this->folder . $key)) {
			unlink($this->folder . $key);
		}
	}

	private $flushOnDestruct = false;

	/**
	 * Flush all values 
	 * 
	 * @param bool $onDestruct Delay flush until current script run ends by 
	 * default so cached values can still be used. For example cached record 
	 * relations will function until the script ends.
	 * 
	 * @return bool
	 */
	public function flush($onDestruct = true) {

		if ($onDestruct) {
			$this->flushOnDestruct = true;
			return true;
		}
		
		\IFW::app()->debug("Flush cache");
		$this->cache = [];
	
		$this->folder->delete();
		$this->folder->create(0777);

		return true;
	}

	public function __destruct() {
		if ($this->flushOnDestruct) {
			$this->flush(false);
		}
	}

	public function isSupported() {
		$folder = IFW::app()->getConfig()->getDataFolder()->getFolder('diskcache');
		
		if(!$folder->isWritable()) {
			return false;
		}
		
		if(!$folder->exists()) {
			$folder->create();
			$folder->chmod(0777);
		}
		
		return true;
	}

}
