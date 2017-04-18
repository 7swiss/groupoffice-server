<?php
namespace IFW\Data;

use IFW;
use Exception;

/**
 * Base object class of all objects.
 * 
 * It implements:
 * 
 * ==Setters and getters
 * 
 * Setters are functions that start with "set" and have only one $value 
 * argument.
 * 
 * Getters are functions that start with "get" and have no arguments.
 * 
 * If you implement setFoo($value) {} and getFoo() for example you can access
 * that "foo" as a property:
 * 
 * $object->foo = "bar"; 
 * 
 * will call $object->setFoo();
 * 
 * ==Configurable
 * Objects are configurable by the main config.php Any Object property can be 
 * defined in config.php:
 * 
 * return [
 *	"Namespace\\ClassName" => ['propertyName => 'value']
 * ]; 
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class Object { 
	
	public function __construct() {
		$this->applyConfig();
	}

	/**
	 * Applies config options to this object
	 */
	private function applyConfig(){
		$className = static::class;		
		if(isset(IFW::app()->getConfig()->classConfig[$className])){			
			foreach(IFW::app()->getConfig()->classConfig[$className] as $key => $value){
				$this->$key = $value;
			}
		}		
	}
	
	/**
	 * Returns the name of this class.
	 * 
	 * @param string the name of this class. eg. 'IFW\Db\Record'
	 */
	public static function getClassName() {
		return static::class;
	}
	
	/**
	 * Returns the shortName of this class.
	 * 
	 * @param string the short name of this class. eg. 'IFW\Db\Record'=>'Record'
	 */
	public static function getShortName() {
		$className = static::getClassName();
		return substr($className, (strrpos($className, '\\')+1));
	}
	
	
	
	/**
	 * Get the namespace of this class
	 * 
	 * @param string eg 'IFW\Db';
	 */
	public static function getNamespace() {
		$className = static::getClassName();
		return substr($className, 0, strrpos($className, '\\'));
	}
	
	
	/**
	 * Magic getter that calls get<NAME> functions in objects
	 
	 * @param string $name property name
	 * @return mixed property value
	 * @throws Exception If the property setter does not exist
	 */
	public function __get($name)
	{			
		$getter = 'get'.$name;

		if(method_exists($this,$getter)){
			return $this->$getter();
		}else
		{
			throw new Exception("Can't get not existing property '$name' in '".static::class."'");			
		}
	}		
	
	/**
	 * Magic function that checks the get<NAME> functions
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		$getter = 'get' . $name;
		if (method_exists($this, $getter)) {
			// property is not null
			return $this->$getter() !== null;
		} else {
			return false;
		}
	}

	/**
	 * Magic properties can't be unset unless you implement logic to this
	 * 
	 * In most cases you want to set the property to null.
	 * 
	 * @param string $name
	 * @throws Exception2
	 */
	public function __unset($name) {
		throw new Exception("Can't unset magic property $name");
	}

	/**
	 * Magic setter that calls set<NAME> functions in objects
	 * 
	 * @param string $name property name
	 * @param mixed $value property value
	 * @throws Exception If the property getter does not exist
	 */
	public function __set($name,$value)
	{
		$setter = 'set'.$name;
			
		if(method_exists($this,$setter)){
			$this->$setter($value);
		}else
		{				
			
			$getter = 'get' . $name;
			if(method_exists($this, $getter)){
				
				//Allow to set read only properties with their original value.
				//http://stackoverflow.com/questions/20533712/how-should-a-restful-service-expose-read-only-properties-on-mutable-resources								
//				$errorMsg = "Can't set read only property '$name' in '".static::class."'";
				//for performance reasons we simply ignore it.
				\IFW::app()->getDebugger()->debug("Discarding read only property '$name' in '".static::class."'");
			}else {
				$errorMsg = "Can't set not existing property '$name' in '".static::class."'";
				throw new Exception($errorMsg);
			}						
		}
	}
	
	
	private static $publicProperties = [];
	
	
	private static function hasPublicProperty($name) {
		
		if(!isset(self::$publicProperties[static::class])) {
			self::$publicProperties[static::class] = [];
			
			$reflection = new \ReflectionClass(static::class);   
			$props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
			
			foreach($props as $prop) {
				self::$publicProperties[static::class][] = $prop->name;
			}
			
		}
		return in_array($name, self::$publicProperties[static::class]);
	}
	
	/**
	 * Check if a writable propery exists
	 * 
	 * public properties and setter functions are checked.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasWritableProperty($name){
		if(static::hasPublicProperty($name)){
			return true;
		}else
		{		
			return method_exists($this,'set'.$name);
		}
	}
	
	/**
	 * Check if a readable propery exists
	 * 
	 * public properties and setter methods are checked
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasReadableProperty($name){
		if(static::hasPublicProperty($name)){
			return true;
		}else
		{		
			return method_exists($this,'get'.$name);
		}
	}
	
	/**
	 * Get the module this object belongs to.
	 * 
	 * 
	 * @return string|boolean eg 'GO\Core\Modules\Modules\Contacts\Module'
	 */
	public static function findModuleName() {
		
		$cacheKey = 'module-name-'.static::class;
		$moduleName = GO()->getCache()->get($cacheKey);
		if($moduleName) {
			return $moduleName;
		}
		
		$modules = IFW::app()->getModules()->toArray();
		rsort($modules);		

		
		foreach($modules as $module) {
			$namespace = substr($module, 0, strrpos($module, '\\'));
			if(strpos(static::class, $namespace) === 0) {
				GO()->getCache()->set($cacheKey, $module);
				return $module;
			}
		}
		
		return false;
	}
}