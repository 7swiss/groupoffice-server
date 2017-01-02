<?php
namespace IFW\Modules;

use ArrayAccess;
use ArrayIterator;
use IFW;
use IFW\Util\ClassFinder;
use IteratorAggregate;

/**
 * Collection of {@see Module}
 * 
 * An instance of this collection is available by calling:
 * 
 * ````````````````````````````````````````````````````````````````````````````
 * $modules = IFW::app()->getModules();
 * 
 * foreach($modules as $moduleClassName) {
 *	echo $moduleClassName;
 * }
 * ````````````````````````````````````````````````````````````````````````````
 * 
 */
class ModuleCollection implements ArrayAccess, IteratorAggregate{
	
	protected $modules;	
	/**
	 * 
	 * @param boolean $installedOnly Return only installed modules by default
	 */
	public function __construct() {
		$cf = new ClassFinder();
		$this->modules = $cf->findByParent(ModuleInterface::class);	
	}

	public function offsetExists($offset) {		
		return isset($this->modules[$offset]);
	}

	public function offsetGet($offset) {
		return $this->modules[$offset];
	}

	public function offsetSet($offset, $value) {
		IFW::app()->getCache()->flush();
		
		$this->modules[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->modules[$offset]);		
		IFW::app()->getCache()->flush();
	}

	public function getIterator() {
		return new ArrayIterator($this->modules);
	}
	
	/**
	 * Check if a module is present and installed
	 * 
	 * @param string $name eg "GO\Modules\Contacts\Module"
	 * @return boolean
	 */
	public function has($name) {
		return in_array($name, $this->modules);
	}
	
	/**
	 * Get the modules as array
	 * 
	 * @return array
	 */
	public function toArray() {
		return $this->modules;
	}
}