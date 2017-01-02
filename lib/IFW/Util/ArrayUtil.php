<?php
namespace IFW\Util;

class ArrayUtil {
	public $array;
	
	public function __construct(Array $array) {
		$this->array = $array;
	}
	
	/**
	 * Find the key in an array by a callable function.
	 * 
	 * Eg.
	 * <code>
	 * 
	 * $arr = [3,5,9];
	 * 
	 * $findGreatherThan = 4;
	 * 
	 * $arrayObject = new \IFW\Util\ArrayUtil($arr);
	 *	
	 * $key = $arrayObject->findKey(function($i) use ($findGreatherThan){
	 *		return $i >= $findGreatherThan;
	 * });	
	 * 
	 * //$key is 1 (value 5)
	 * </code>
	 * 
	 * @param \IFW\Util\callable $fn
	 * @return mixed
	 */
	public function findKey(callable $fn) {		
		foreach($this->array as $key => $value) {
			if(call_user_func($fn, $value)) {
				return $key;
			}
		}
		
		return false;
	}
}
