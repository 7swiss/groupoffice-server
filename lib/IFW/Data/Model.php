<?php

namespace IFW\Data;

use IFW;
use IFW\Data\ArrayableInterface;
use IFW\Data\Exception\NotArrayable;
use IFW\Data\Object;
use IFW\Util\DateTime;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * The abstract model class. 
 * 
 * Models implement validation by default and can be converted into an Array for
 * the API.
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class Model extends Object implements ArrayableInterface{
	
	/**
	 * Define the properties that are returned by {@see toArray()} by default.
	 * 
	 * This defaults to all public properties and public "get" functions without 
	 * parameters.
	 * 
	 * Note: You can't use the * wildcard in this function.
	 * 
	 * @param string eg "id,name,creator[id,username,photoBlobId]"
	 */
	public static function getDefaultReturnProperties(){
		
		$cacheKey = static::getClassName().'-DefaultReturnProperties';
		
		$ret = GO()->getCache()->get($cacheKey);
		if($ret) {
			return $ret;
		}
		
		$arr = [];
		$reflectionObject = new ReflectionClass(static::class);
		$methods = $reflectionObject->getMethods(ReflectionMethod::IS_PUBLIC);
		
		foreach($methods as $method){
			/* @var $method ReflectionMethod */
			
			if($method->isStatic()){
				continue;
			}
			
			$params = $method->getParameters();			
			foreach($params as $p) {
				/* @var $p \ReflectionParameter */
				if(!$p->isDefaultValueAvailable()) {
					continue 2;
				}
			}
			if(substr($method->getName(), 0,3) == 'get'){
				$arr[] = lcfirst(substr($method->getName(),3));
			}			
		}
		
		$props = $reflectionObject->getProperties(ReflectionProperty::IS_PUBLIC);
		
		foreach($props as $prop){
			if(!$prop->isStatic()){
				$arr[]=$prop->getName();
			}
		}
		
		$ret = implode(',',$arr);
		
		GO()->getCache()->set($cacheKey, $ret);
		
		return $ret;
	}	

	/**
	 * Convert model into array for API output.
	 *
	 * It will only convert scalars or objects that implement {@see ArrayableInterface}.
	 * 
	 * It's also possible to get related attributes eg.	'name,user[username]';
	 * This will fetch the name attribute of the model but also the username of the
	 * user relation. 
	 * 
	 * The '*' char can be used to get all default properties + mon default:
	 * 
	 * name,owner,hasmanyRelation[*,nonDefaultProp]
	 * 
	 * Use brackets to specify specific attributes of the relational models.
	 *
	 * <code>
	 * $model = User::findByPk(1);
	 *
	 * $allOfTheModel = $model->toArray();
	 *
	 * $someButWithRelational = $model->toArray('name,user[username]');
	 * 
	 * $allAndWithUser = $model->toArray('*,user[*]');
	 * 
	 * 
	 * 
	 * $response->data['project'] = Project::findByPk($projectId)->toArray(
	 *				'*,contact[*,company[name,addresses[formatted]]'	
	 *				);
	 * 
	 * </code>
	 * 
	 * Recursion is also possible with the & operator:
	 * 
	 * <code>
	 * "id,description,computedStartTime,executor[username],completedAt,sortOrder,parentTaskId,tasks[&]"
	 * </code>
	 * 
	 * @param string|ReturnProperties|array $properties
	 * @return array
	 */
	public function toArray($properties = null) {
		
		if(!($properties instanceof ReturnProperties)) {			
			$properties = new ReturnProperties($properties, $this->getDefaultReturnProperties());		
		}

		$arr = [];
		
		foreach ($properties as $propName => $subReturnProperties) {
			//recursive
			if($subReturnProperties=='&'){
				$subReturnProperties = $properties;
			}

			try {
				$arr[$propName] = $this->convertValue(ModelHelper::getValue($this, $propName), $subReturnProperties);				
			} catch (NotArrayable $e) {
				IFW::app()->debug("Skipped prop ".$this->getClassName()."::".$propName." because it's not scalar or ArrayConvertable");
			}
		}
		

		return $arr;
	}
	
	/**
	 * Converts value to an array if supported
	 * 
	 * 
	 * @param type $value
	 * @param type $subReturnProperties
	 * @return DateTime
	 * @throws NotArrayable
	 */
	private function convertValue($value, $subReturnProperties) {
		if($value instanceof ArrayableInterface){
			return $value->toArray($subReturnProperties);
		}else if($value instanceof DateTime) {
			
			return $value->format(DateTime::FORMAT_API);
		}
		elseif(is_array($value)){
			//support an array of models too
			if(isset($value[0])) {
				$arr = [];
				foreach($value as $key => $v){
					$arr[$key] = $this->convertValue($v, $subReturnProperties);
				}			
				return $arr;
			}
			return $value;
			
		} else if (is_scalar($value) || is_null($value)) {
			return $value;			
		}else
		{
			throw new NotArrayable();
		}
	}
	
//	/**
//	 * Get a single attribute
//	 * 
//	 * Also supports nested properties.
//	 * 
//	 * So you can query "model.prop.attribute"
//	 * 
//	 * @param string $path
//	 * @return mixed
//	 */
//	public function getAttribute($path) {
//		
//		$parts = explode('.', $path);
//	
//		$attr = $this;
//		
//		foreach($parts as $part) {	
//			if(!isset($attr->$part)) {				
//				IFW::app()->debug($this->className()."->$part returned null");				
//				return null;
//			}
//			$attr = $attr->$part;
//		}
//		
//		return $attr;		
//	}
	
	

	/**
	 * Set public properties with key value array.
	 * 
	 *
	 * <p>Example:</p>
	 * <code>
	 * $model = User::findByPk(1);
	 * $model->setValues(['username' => 'admin']);
	 * $model->save();
	 * </code>
	 *
	 * 
	 * @param array $properties
	 * @return \static
	 */
	public function setValues(array $properties) {		
		ModelHelper::setValues($this, $properties);
		return $this;
	}
}