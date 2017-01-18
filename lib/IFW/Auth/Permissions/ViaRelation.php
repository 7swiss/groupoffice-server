<?php
namespace IFW\Auth\Permissions;

use Exception;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;
use IFW\Orm\Relation;


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
			throw new Exception("Relation $relationName is not set in ".$this->record->getClassName().", Maybe you didn't select the key?");
		}
		
		return $model->permissions->can($permissionType, $user);
	}
	
	public function toArray($properties = null) {
		return null;
	}
	
	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		
		$recordClassName = $this->recordClassName;

		$relation = $recordClassName::getRelation($this->relationName);
		
		/* @var $relation  Relation */
		
	
	  $toRecordName = $relation->getToRecordName();
		$subquery = isset($relation->query) ? clone $relation->query : new Query();	
		$subquery->tableAlias($this->relationName);
		
		
		if($relation->getViaRecordName() !== null) {
			
			$linkTableAlias = $relation->getName().'Link';
			
			//ContactTag.tagId -> tag.id
			$on = '';
			foreach($relation->getViaKeys() as $fromField => $toField) {
				$on .= '`'.$linkTableAlias.'`.`'.$fromField.'`=`'.$subquery->tableAlias.'`.`'.$toField.'`'; 
			}
			//join ContactTag
			$subquery->join($this->viaRecordName, $linkTableAlias, $on);

			foreach($relation->getKeys() as $myKey => $theirKey) {
				$subquery->andWhere($linkTableAlias.'.'.$theirKey.' = `'.$subquery->tableAlias.'`.`'. $myKey.'`');			
			}
		}else
		{
			foreach($relation->getKeys() as $myKey => $theirKey) {
				$subquery->andWhere('`'.$subquery->tableAlias.'`.`'.$theirKey. '` = `'.$query->tableAlias.'`.`'. $myKey.'`');			
			}
		}
		
		self::$enablePermissions = true;
		$store = $toRecordName::find($subquery);		
		self::$enablePermissions = false;
		$query->andWhere(['EXISTS', $store]);
		$query->skipReadPermission();
		
	}

}