<?php
namespace IFW\Auth\Permissions;

use Exception;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;
use IFW\Orm\Relation;

/**
 * Permissions model to relay all permission checks to a relation.
 * Typically used in identifying relations like emailAddresses for a contact.
 * 
 * {@see \GO\Modules\GroupOffice\Contacts\Model\EmailAddress}
 */
class ViaRelation extends Model {
	
	private $relationName;
	
	private $requirePermissionType;
	
	/**
	 * Constructor
	 * 
	 * @param string $relationName
	 * @param int $requirePermissionType 
	 * By default read permissions on the relation is required to read and update 
	 * permission is required to update or delete. Because it's probably allowed 
	 * to delete a contact's e-mail addreess when you have update permissions for 
	 * the contact. But you can optionally supply a hard coded permission type.
	 * For example permissions model should raise the permission type to 
	 * PERMISSION_CHANGE_PERMISSIONS like in {@see \GO\Modules\GroupOffice\Contacts\Model\ContactGroup}
	 */
	public function __construct($relationName, $requirePermissionType = null) {
		$this->relationName = $relationName;
		$this->requirePermissionType = $requirePermissionType;
	}
	
	protected function internalCan($permissionType, UserInterface $user) {		
		
		$relationName = $this->relationName;
		
		//see $requirePermissionType for explanation
		if(isset($this->requirePermissionType)) {
			$permissionType = $this->requirePermissionType;
		} else {
			$permissionType = $permissionType == self::PERMISSION_READ ? self::PERMISSION_READ : self::PERMISSION_UPDATE;
		}
		
		
		$model = $this->record->{$relationName};

		if(!isset($model)) {
			throw new Exception("Relation $relationName is not set in ".$this->record->getClassName().", Maybe you didn't select or set the key?");
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
				$on .= '`'.$linkTableAlias.'`.`'.$fromField.'`=`'.$subquery->getTableAlias().'`.`'.$toField.'`'; 
			}
			//join ContactTag
			$subquery->join($this->viaRecordName, $linkTableAlias, $on);

			foreach($relation->getKeys() as $myKey => $theirKey) {
				$subquery->andWhere($linkTableAlias.'.'.$theirKey.' = `'.$subquery->getTableAlias().'`.`'. $myKey.'`');			
			}
		}else
		{
			foreach($relation->getKeys() as $myKey => $theirKey) {
				$subquery->andWhere('`'.$subquery->getTableAlias().'`.`'.$theirKey. '` = `'.$query->getTableAlias().'`.`'. $myKey.'`');			
			}
		}
		
		self::$enablePermissions = true;
		$store = $toRecordName::find($subquery);		
		self::$enablePermissions = false;
		$query->andWhere(['EXISTS', $store]);
		$query->skipReadPermission();
		
	}

}