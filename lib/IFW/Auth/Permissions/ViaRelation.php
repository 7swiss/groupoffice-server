<?php
namespace IFW\Auth\Permissions;

use Exception;
use IFW\Auth\UserInterface;
use IFW\Orm\Record;


class ViaRelation extends Model {
	
	private $relationName;
	
	public function __construct($relationName) {
		$this->relationName = $relationName;
	}
	
	protected function internalCan($permissionType, UserInterface $user) {		
		
		$relationName = $this->relationName;
		
		$permissionType = $permissionType == self::PERMISSION_READ ? self::PERMISSION_READ : self::PERMISSION_UPDATE;
		
		$model = $this->record->{$relationName};

		if(!isset($model)) {
			GO()->debug($this->record);
			throw new Exception("Relation $relationName is not set in ".$this->record->getClassName().", Maybe you didn't select the key?");
		}
		
		return $model->permissions->can($permissionType, $user);
	}

}