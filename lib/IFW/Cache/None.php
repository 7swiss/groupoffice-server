<?php
namespace IFW\Cache;

use IFW\Cache\CacheInterface;

/**
 * Cache implementation that uses serialized objects in files on disk.
 * The cache is persistent accross requests.
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class None implements CacheInterface{
	
	

	/**
	 * Store any value in the cache
	 * @param string $key
	 * @param mixed $value Will be serialized
	 * @param int $ttl Seconds to live
	 */
	public function set($key, $value, $ttl=0){
		return true;
	}
	
	/**
	 * Get a value from the cache
	 * 
	 * @param string $key
	 * @return mixed Stored value or NULL if not found 
	 */
	public function get($key){		
		return null;
	}
	
	/**
	 * Delete a value from the cache
	 * 
	 * @param string $key 
	 */
	public function delete($key){
		return true;
	}
	/**
	 * Flush all values 
	 * 
	 * @return boolean
	 */
	public function flush(){
		return true;
	}
	
	public function supported() {
		return true;
	}
}