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

	private $ttls;
	private $ttlFile;
	private $ttlsDirty = false;
	private $folder;
	private $time;

	public function __construct() {
		$this->folder = IFW::app()->getConfig()->getTempFolder(true, false)->getFolder('diskcache');

		$this->folder->create();

		$this->ttlFile = $this->folder->getFile('ttls.txt');

		$this->load();

		$this->time = time();
	}

	private function load() {
		if (!isset($this->ttls)) {

			if ($this->ttlFile->exists()) {
				$data = $this->ttlFile->getContents();
				$this->ttls = unserialize($data);
			} else {
				$this->ttls = array();
			}
		}
	}

	/**
	 * Store any value in the cache
	 * @param string $key
	 * @param mixed $value Will be serialized
	 * @param int $secondsToLive Seconds to live
	 */
	public function set($key, $value, $secondsToLive = 0) {

		//don't set false values because unserialize returns false on failure.
		if ($key === false)
			return true;

		$key = File::stripInvalidChars($key, '-');

		if ($secondsToLive) {
			$this->ttls[$key] = $this->time + $secondsToLive;
			$this->ttlsDirty = true;
		}

		$file = $this->folder->getFile($key);

		$success = $file->putContents(serialize($value));

		return $success;
	}

	/**
	 * Get a value from the cache
	 * 
	 * @param string $key 
	 * @return mixed null if it doesn't exist
	 */
	public function get($key) {

		$key = File::stripInvalidChars($key, '-');

		$file = $this->folder->getFile($key);

		if (!empty($this->ttls[$key]) && $this->ttls[$key] < $this->time) {
			$file->delete();
			return null;
		} elseif (!$file->exists()) {
			return null;
		} else {

			$unserialized = unserialize($file->getContents());

			if ($unserialized === false) {
				trigger_error("Could not unserialize cache from file " . $key);
				$this->delete($key);
				return null;
			} else {
				return $unserialized;
			}
		}
	}

	/**
	 * Delete a value from the cache
	 * 
	 * @param string $key 
	 */
	public function delete($key) {
		$key = File::stripInvalidChars($key, '-');

		unset($this->ttls[$key]);
		$this->ttlsDirty = true;
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


		$this->ttls = [];
		$this->ttlsDirty = true;

		$this->folder->delete();
		$this->folder->create(0777);

		return true;
	}

	public function __destruct() {

		if ($this->flushOnDestruct) {
			$this->flush(false);
		}

		if ($this->ttlsDirty)
			$this->ttlFile->putContents(serialize($this->ttls));
	}

	public function supported() {
		return true;
	}

}
