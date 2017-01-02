<?php
namespace IFW\Util;

use IFW;
use IFW\Fs\Folder;

/**
 * Finds classes within Group-Office
 * 
 */
class ClassFinder{	
	
	private $namespace;
	
	/**
	 * Set a specific namespace to search in.
	 * 
	 * @param string $namespace eg. "GO\Modules\Contacts"
	 */
	public function setNamespace($namespace) {
		$namespace = ltrim($namespace, '\\');		
		
		//find folder with namespacxe
		$prefixes = $this->getPrefixes();
		foreach($prefixes as $nsPrefix => $paths){
			if(strpos($namespace, $nsPrefix) === 0) {
				
				$fullPath = realpath($paths[0].'/'.str_replace("\\", '/', substr($namespace, strlen($nsPrefix))));
				$folder = new Folder($fullPath);
				
				$this->namespace = [$namespace, $folder];
				return true;
			}
		}
		
		throw new \Exception("Could not find namespace '$namespace'");
	}
	
	/**
	 * 
	 * @param array $exclude Exclude vendor packages
	 * @return array eg:
	 * 
	 *  ["IFW\"]=>
	 *  array(1) {
	 *    [0]=>
	 *    string(35) "/var/www/groupoffice-server/lib/IFW"
	 *  }
	 */
	private function getPrefixes() {
		$prefixes = IFW::app()->getClassLoader()->getPrefixesPsr4();
		
		$p = [];		
		
		foreach($prefixes as $prefix => $paths) {
			
			$trimmedPrefix = trim($prefix, '\\');
			if($trimmedPrefix == 'IFW' || $trimmedPrefix == 'GO'){			
				$p[$prefix] = $paths;
			}
		}
		
		return $p;		
	}
	
	/**
	 * Get the source file by class name
	 * 
	 * @example
	 * ```````````````````````````````````````````````````````````````````````````
	 * $className = \GO\Modules\GroupOffice\Contacts\Model\Contact::class;
	 *		
	 * $classFinder = new \IFW\Util\ClassFinder();
	 * $file = $classFinder->classNameToFile($className);
	 *
	 * echo $file;
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string $className
	 * @return boolean|\IFW\Fs\File
	 */
	public function classNameToFile($className) {
		$prefix = $this->findPrefixForClassName($className);
		$path = StringUtil::replaceOnce($prefix[0], realpath($prefix[1]).'/', $className);
		$path = str_replace('\\','/', $path);
	
		$file = new IFW\Fs\File($path.'.php');
		if(!$file->exists()) {
			return false;
		}
		
		return $file;
	}
	
	private function findPrefixForClassName($className) {
		foreach($this->getPrefixes() as $prefix => $paths) {
			if(strpos($className, $prefix) === 0) {
				return [$prefix, $paths[0]];
			}
		}
		
		return false;
	}
	
	/**
	 * Find all classes
	 * 
	 * @param string[] Full class name without leading "\" eg. ["IFW\App"]
	 */
	public function find(){		
		
		if(isset($this->namespace)) {			
			return $this->getSubClassesFromFolder($this->namespace[1], $this->namespace[0]);			
		}
		
		$allClasses = [];	

		$prefixes = $this->getPrefixes();
		foreach($prefixes as $namespace => $paths){
			$folder = new Folder(realpath($paths[0]));
			$allClasses = array_merge(
							$allClasses, 
							$this->getSubClassesFromFolder($folder, trim($namespace, '\\')));
		}
		
		sort($allClasses);
		
		return $allClasses;
	}
	
	
	/**
	 * Find class names that are sub classes of the given class or implement this as an interface
	 * 
	 * @param string $name Parent class name or interface eg. IFW\Orm\Record::class
	 * @param string[] Full class name eg. ["IFW\App"]
	 */
	public function findByParent($name) {
		return $this->findBy(function($className) use ($name) {
				$reflection = new \ReflectionClass($className);
				return $reflection->isInstantiable() && ($reflection->isSubclassOf($name) || in_array($name, $reflection->getInterfaceNames()));
		});
	}
	
	/**
	 * Find class names by a closure function
	 * 
	 * If you return true in the closure function it will be included in the results.
	 * The closure funciton is called with the class name
	 * 
	 * @param \Closure $fn
	 * @param string[] Full class name eg. ["IFW\App"]
	 */
	public function findBy(\Closure $fn) {
		$classes = $this->find();

		$r = [];
		foreach($classes as $class) {
			if($fn($class)) {
				$r[] = $class;
			}
		}
		
		return $r;
	}
	
	private function getSubClassesFromFolder(Folder $folder, $namespace) {	
		
		$classes = [];
		foreach($folder->getFiles() as $file) {
			
			if($file->getExtension() == 'php') {
			
				$name = $file->getNameWithoutExtension();
				
				//Skip the special IFW class that is not in the correct PSR-4 location because it's the global IFW class to access the application instance
				if($name === 'IFW') {
					continue;
				}
				$className = $namespace.'\\'.$name;
				
				if(!class_exists($className)) {				
					continue;
				}			
				
				$classes[] = $className;
			}
		}
		
		foreach($folder->getFolders() as $folder) {
			$classes = array_merge($classes, $this->getSubClassesFromFolder($folder, $namespace.'\\'.$folder->getName()));			
		}
		
		return $classes;
	}
}