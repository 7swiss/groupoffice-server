<?php
namespace IFW\Event;


/**
 * Enable events for an object
 * 
 * All objects within Group-Office are searched for a static method called 
 * "defineEvents()". In this function you can call
 * 
 * Object::on(Object::EVENT_SOME, self, 'listenerMethod');
 * 
 * Event names should be defined as constants prefixed with EVENT_
 * 
 * See {@see \IFW\Db\AbstractRecord} for an example.
 */
trait EventEmitterTrait {
	
	private $objectListeners = [];
	
	
	/**
	 * Set to true to disable events
	 * 
	 * @var boolean 
	 */
	public static $disableEvents = false;
	
	
	/**
	 * Add an event listener
	 * 
	 * @param int $event Defined in constants prefixed by EVENT_
	 * @param callable $fn 
	 * @return int $index Can be used for removing the listener.
	 */
	public static function on($event, $class, $method){		
		StaticListeners::singleton()->on(static::class, $event, $class, $method);
	}
	
	/**
	 * Fire an event
	 * 
	 * @param int $event Defined in constants prefixed by EVENT_
	 * @param mixed $args Multiple extra agruments to be passed to the callbacks
	 * @return boolean
	 */
	public function fireEvent($event){
		
		if(EventEmitterTrait::$disableEvents) {
			return true;
		}
		
		$args = func_get_args();
		
		//shift $event
		array_shift($args);	
		
		if(!StaticListeners::singleton()->fireEvent(static::class, $event, $args)) {
			return false;
		}
		
		if(!isset($this->objectListeners[$event])) {
			return true;
		}
		
		foreach($this->objectListeners[$event] as $listener) {
//			
//			\IFW::app()->debug("FIring listener $event ".var_export($listener, true));
//			
			$return = call_user_func_array($listener, $args);			
			if($return === false){
				
			\IFW::app()->debug('Event listerner: '.var_export($listener, true).' returned false');
				
				return false;
			}
		}
		
		return true;
	}	

	/**
	 * Fire's an event on a static class
	 * 
	 * @param int $event
	 * @return boolean
	 */
	public static function fireStaticEvent($event) {
		
		if(EventEmitterTrait::$disableEvents) {
			return true;
		}
		
		$args = func_get_args();
		
		//shift $event
		array_shift($args);
		
		if(!StaticListeners::singleton()->fireEvent(static::class, $event, $args)) {
			return false;
		}		
		return true;
	}	
	
	
	/**
	 * Add an event listener
	 * 
	 * @param int $event Defined in constants prefixed by EVENT_
	 * @param callable $fn 
	 * @param array $params key value of listener arguments. They are looked up by argument name.
	 * @return int $index Can be used for removing the listener.
	 */
	public function attach($event, $fn){
		
		if(!isset($this->objectListeners[$event])){
			$this->objectListeners[$event] = [];
		}
		$this->objectListeners[$event][] = $fn;
		
		return count($this->objectListeners[$event])-1;
	}
	
	/**
	 * Remove a listener
	 * 
	 * @param int $event
	 * @param int $index
	 */
	public function detach($event, $index) {
		unset($this->objectListeners[$event][$index]);
	}	
	
	
//	private function _callListener($listener, $args) {
//		if(!isset($listener['params'])) {
//			 return call_user_func_array($listener['fn'], $args);
//		}else
//		{
//			if(is_array($listener['fn'])) {
//				$rFn = new ReflectionMethod($listener['fn'][0], $listener['fn'][1]);
//			}else
//			{
//				$rFn = new ReflectionFunction($listener['fn']);
//			}
//			$rParams = $rFn->getParameters();
//			$rParams = array_slice($rParams, count($args));		
//			
//			foreach($rParams as $param) {				
//				$paramName = $param->getName();
//				
//				if (!isset($listener['params'][$paramName]) && !$param->isOptional()) {
//					throw new Exception("Bad event listener. Missing argument '" .$paramName. "' for action method '" . $rFn->getName() . "'");
//				}
//				$args[] = isset($listener['params'][$paramName]) ? $listener['params'][$paramName] : $param->getDefaultValue();
//			}
//
//			return call_user_func_array($listener['fn'], $args);			
//		}
//	}
}