<?php
namespace IFW\Data;

/**
 * Some functions to make models work.
 */
class ModelHelper {
		
	/**
	 * Helper function to make setValues work as if the values were applied 
	 * externally. Otherwise it would be possible to set private or protected 
	 * values.
	 * 
	 * @param object $model
	 * @param array $properties
	 */
	public static function setValues(Model $model, array $properties) {
		foreach ($properties as $propName => $value) {
			$model->{$propName} = $value;						
		}
	}
	
	/**
	 * Helper function to get a value from an object externally.
	 * 
	 * @param \IFW\Data\Model $model
	 * @param string $propName
	 * @return mixed
	 */
	public static function getValue(Model $model, $propName) {
		return $model->$propName;
	}
}
