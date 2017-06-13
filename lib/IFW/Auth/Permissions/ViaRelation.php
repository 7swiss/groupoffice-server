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
	
	protected $relationName;
	
	
	/**
	 * Constructor
	 * 
	 * @param string $relationName
	 */
	public function __construct($relationName) {
		$this->relationName = $relationName;		
	}
	
	protected function internalCan($permissionType, UserInterface $user) {		
		
		$relatedRecord = $this->getRelatedRecord();
		
		if($permissionType != self::PERMISSION_READ) {
			$permissionType = $relatedRecord->isNew() ? self::PERMISSION_CREATE : self::PERMISSION_WRITE;		
		}
		
		return $relatedRecord->permissions->can($permissionType, $user);
	}
	
	
	/**
	 * Get the record the permissions are relayed to.
	 * 
	 * @return \IFW\Orm\Record
	 * @throws Exception
	 */
	protected function getRelatedRecord() {
		$relationName = $this->relationName;	
		$relatedRecord = $this->record->{$relationName};

		if(!isset($relatedRecord)) {
			throw new Exception("Relation $relationName is not set in ".$this->record->getClassName().", Maybe you didn't select or set the key?");
		}
		
		return $relatedRecord;
	}

	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		

		if($query->getRelation()) {
			//check if we're doing a relational query from the relation set in $this->relationName.
			//If so we can skip the permissions
			$parent = $query->getRelation()->findParent();

			if($parent && $parent->getName() == $this->relationName) {
				//query is relational and coming from the ViaRelation so no extra query is needed.
				return;
			}
		}
		
		
		$recordClassName = $this->recordClassName;

		$relation = $recordClassName::getRelation($this->relationName);
		
		if(!$relation) {
			throw new \Exception("ViaRelation error: '". $this->relationName . "' doesn't exist in '" . $recordClassName . "'");
		}
		
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
			$viaRecordName = $relation->getViaRecordName();
			$subquery->join($viaRecordName::tableName(), $linkTableAlias, $on);

			foreach($relation->getKeys() as $myKey => $theirKey) {
				$subquery->andWhere($linkTableAlias.'.'.$theirKey.' = `'.$query->getTableAlias().'`.`'. $myKey.'`');			
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
		$query->allowPermissionTypes([\IFW\Auth\Permissions\Model::PERMISSION_READ]);
		
	}

}