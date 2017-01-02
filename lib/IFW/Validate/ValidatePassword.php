<?php
namespace IFW\Validate;

use IFW\Data\Model;

/**
 * Validates a password attribute of the ActiveRecord
 * 
 * <p>eg. in ActiveRecord do:</p>
 * 
 * <code>
 * protected static function defineValidationRules() {
 *	
 *		self::getColumn('username')->required=true;
 *		
 *		return array(
 *				new ValidateEmail("email"),
 *				new ValidateUnique('email'),
 *				new ValidateUnique('username'),
 *        new ValidatePassword('password') //Also encrypts it on success
 *		);
 *	}
 * </code>
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ValidatePassword extends Base {
	
	/**
	 * Enable strength check
	 * 
	 * @var boolean 
	 */
	public $enabled = true;
	
	/**
	 * Minimum characters
	 * 
	 * @var int 
	 */
	private $minLength=6;
	
	/**
	 * Require an uppercase char
	 * 
	 * @var bool 
	 */
	private $requireUpperCase=true;
	
	/**
	 * Require a lowercase char
	 * 
	 * @var bool 
	 */
	private $requireLowerCase=true;
	
	/**
	 * Require a number
	 * 
	 * @var bool 
	 */
	private $requireNumber=true;
	
	/**
	 * Require a non alpha nummeric char
	 * 
	 * @var bool 
	 */
	private $requireSpecialChars=true;
	
	/**
	 * Minimum amount of unique characters
	 * 
	 * @var int 
	 */
	private $minUniqueChars=3;
	
	/**
	 * Creates a new validator
	 * 
	 * @param string $id Password column
	 */
	public function __construct($id) {
		parent::__construct($id);
	}
	
	public function validate(Model $model) {
		
		//Don't validate if not modified
		if(!$model->isModified($this->getId())){
			return true;
		}
		
		//Get old value because it's encrypted
		$password = $model->{$this->getId()};
		
		if($this->enabled){
			$this->errorInfo=[];
			if($this->minLength && strlen($password)<$this->minLength){
				$this->errorCode='weakPassword';
				$this->errorInfo['minLength'] = $this->minLength;			
			}

			if($this->requireUpperCase && !preg_match('/[A-Z]/', $password)){
				$this->errorCode='weakPassword';
				$this->errorInfo['requireUpperCase'] = true;

			}

			if($this->requireLowerCase && !preg_match('/[a-z]/', $password)){
				$this->errorCode='weakPassword';
				$this->errorInfo['requireLowerCase'] = true;
			}

			if($this->requireNumber && !preg_match('/[0-9]/', $password)){
				$this->errorCode='weakPassword';
				$this->errorInfo['requireNumber'] = true;		
			}

			if($this->requireSpecialChars && !preg_match('/[^\da-zA-Z]/', $password)){
				$this->errorCode='weakPassword';
				$this->errorInfo['requireSpecialChars'] = true;		
			}

			if($this->minUniqueChars){
				$arr = str_split($password);
				$arr = array_unique($arr);

				if(count($arr)<$this->minUniqueChars){
					$this->errorCode='weakPassword';
					$this->errorInfo['minUniqueChars'] = $this->minUniqueChars;
				}
			}

			if($this->errorCode != ''){
				return false;
			}
		}
		
			
		return true;		
	}
}