<?php
namespace IFW\Event;

use IFW;
use IFW\Util\ClassFinder;
use ReflectionClass;

/**
 * Contains and executes all static event listeners
 * 
 * Static event listeners can be set defined by any class that implements the
 * {@see EventListenerInterface}
 * 
 * This class is not used directly. Objects can use the {@see EventEmiterTrait} 
 * to emit events. Because we need all listeners together in one object this 
 * singleton class  holds them all.
 */
class StaticListeners {
	
	private static $singleton;
	
	private $listeners;
	
	/**
	 * 
	 * @return static
	 */
	public static function singleton() {
		if(isset(self::$singleton)) {
			return self::$singleton;
		}else
		{
			return self::$singleton = new static;
		}
	}
	
	/**
	 * Add an event listener
	 * 
	 * @param int $event Defined in constants prefixed by EVENT_
	 * @param callable $fn 
	 * @return int $index Can be used for removing the listener.
	 */
	public function on($firingClass, $event, $listenerClass, $method){
		
		if(!isset($this->listeners[$firingClass][$event])){
			$this->listeners[$firingClass][$event] = [];
		}
		$this->listeners[$firingClass][$event][] = [$listenerClass, $method];
	}
	
	/**
	 * 
	 * Initialize the static events.
	 * Called in {@see IFW\App::__construct()}
	 * 
	 */
	public function initListeners() {
		if(isset($this->listeners)) {
			return;
		}	
		
		$this->listeners = IFW::app()->getCache()->get('listeners');

		if($this->listeners)
		{
			return;
		}
		
		IFW::app()->debug("Initializing event listeners");
			
		
		//disable events to prevent recursion
		EventEmitterTrait::$disableEvents = true;
		
		foreach(\IFW::app()->getModules() as $module) {
		
			$classFinder = new ClassFinder();		
			$classFinder->setNamespace($module::getNamespace());
		
			$classes = $classFinder->find();

			foreach($classes as $className) {				
				if(!method_exists($className, 'defineEvents')) {
					continue;
				}

				$reflection = new ReflectionClass($className);
				if($reflection->isAbstract()){
					continue;
				}
				$className::defineEvents();				
			}
		}
		
		//disable events to prevent recursion
		EventEmitterTrait::$disableEvents = false;
		
		
		IFW::app()->getCache()->set('listeners', $this->listeners);		
	}
	
	/**
	 * Fire an event and execute all listeners
	 * 
	 * @param string $calledClass
	 * @param int $event
	 * @param mixed[] $args
	 * @return boolean
	 */
	public function fireEvent($calledClass, $event, $args) {

		if(!isset($this->listeners[$calledClass][$event])){
			return true;
		}
		
		foreach($this->listeners[$calledClass][$event] as $listener) {
			$return = call_user_func_array($listener, $args);
			
			if($return === false){
				
				\IFW::app()->debug("Listener returned false for event ".$event." ".var_export($listener, true));
				return false;
			}
		}
		
		return true;
	}
	
}