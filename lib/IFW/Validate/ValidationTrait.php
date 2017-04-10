<?php
namespace IFW\Validate;

trait ValidationTrait {
	private $validationErrors = null;
	
	/**
	 * Validation rules
	 * @var array
	 */
	private static $validators = [];
	
	/**
	 * Define an array of validation rules
	 *
	 * These rules should be checked when saving this model.
	 *
	 * ```````````````````````````````````````````````````````````````````````````
	 * protected static function defineValidationRules() {
	 *
	 * 	return [
	 * 			new ValidateEmail("email")
	 * 	];
	 * }```````````````````````````````````````````````````````````````````````````
	 *
	 * @return Base[]
	 */
	protected static function defineValidationRules() {
		return [];
	}

	/**
	 * Get's the validation rules
	 *
	 * @return Base
	 */
	protected static function getValidationRules() {
		$calledClass = get_called_class();
		if (!isset(self::$validators[$calledClass])) {
			self::$validators[$calledClass] = static::defineValidationRules();
		}

		return self::$validators[$calledClass];
	}


	/**
	 * You can override this function to implement validation in your model.
	 * 
	 * @return boolean
	 */
	public function validate() {
		
		$validators = $this->getValidationRules();		
		foreach ($validators as $validator) {
			if (!$validator->validate($this)) {
				$this->setValidationError(
								$validator->getId(), $validator->getErrorCode(), $validator->getErrorData());
			}
		}
		
		return !$this->hasValidationErrors();
	}

	/**
	 * Return all validation errors of this model
	 * 
	 * @return array 
	 */
	public function getValidationErrors() {
		return $this->validationErrors;
	}

	/**
	 * Get the validationError for the given attribute
	 * If the attribute has no error then fals will be returned
	 * 
	 * @param string $key
	 * @return array eg. array('code'=>'maxLength','info'=>array('length'=>10)) 
	 */
	public function getValidationError($key) {
		$validationErrors = $this->getValidationErrors();
		if (!empty($validationErrors[$key])) {
			return $validationErrors[$key];
		} else {
			return false;
		}
	}

	/**
	 * Set a validation error for the given field.
	 * If the error key is equal to a model attribute name, the view can render 
	 * an error on the associated form field.
	 * The key for an error must be unique.
	 * 
	 * @param string $key 
	 * @param int $code  Error code. {@see \IFW\Validate\ErrorCode} class for general constants
	 * @param string $description Override the default description. Pure info for the API developer. Clients shouldn't use this.
	 * @param array $data Arbitrary data for output to the client
	 */
	protected function setValidationError($key, $code, $description = null, $data = []) {
		
		if(!isset($description)) {
			$description = \IFW\Validate\ErrorCode::getDescription($code);
		}
		
		\IFW::app()->debug("Validation error in ".$this->getClassName().'::'.$key.': '.$code, 'validation', 1);
		
//		\IFW::app()->getDebugger()->debugCalledFrom();
		
		$this->validationErrors[$key] = ['code' => $code, 'description' => $description, 'data' => $data];
	}

	/**
	 * Returns a value indicating whether there is any validation error.
	 * @param string $key attribute name. Use null to check all attributes.
	 * @return boolean whether there is any error.
	 */
	public function hasValidationErrors($key = null) {
		$validationErrors = $this->getValidationErrors();

		if ($key === null) {
			return count($validationErrors) > 0;
		} else {
			return isset($validationErrors[$key]);
		}
	}
}
