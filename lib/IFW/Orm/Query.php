<?php
namespace IFW\Orm;

use IFW\Db\Criteria;
use IFW\Db\Query as DbQuery;

/**
 * Used to build parameters for ActiveRecord::find() database queries
 *
 * @see Record::find()
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Query extends DbQuery {

	/**
	 * Join a relation in the find query. Relation models are fetched together and
	 * can be accessed without the need for an extra select query.
	 * 
	 * It also makes use of the soft delete feature. So if the record has a 
	 * 'deleted' property it will be used in the on clause as well.
	 *
	 * eg. joinRelation('owner') on an addressbooks query allows you to do
	 *
	 * $addressbook->owner->username
	 *
	 * without an extra select query.
	 *
	 * @param string $name
	 * @param array|boolean $selectAttributes Select model attributes that will be 
	 * available when accessing the relation. Can't be used with 
	 * Relation::TYPE_HAS_MANY.
	 * 1 False will select nothing
	 * 2 True will select all 
	 * 3 An array can specify the columns (recommended for performance)
	 * 
	 * Example:
	 * ```````````````````````````````````````````````````````````````````````````
	 * $query = (new Query())->joinRelation('group', ['name']);
	 *	
	 * $usersWithGroup = User::find($query);
	 *	
	 * foreach($usersWithGroup as $user){
	 *		echo $user->username.":".$user->group->name."\n";
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * $user->group Does not need an additional select query now.
	 * 
	 * @param string $type INNER, LEFT or RIGHT
	 * @param Criteria|array|string $on The criteria used in the ON clause {@see Criteria::normalize()}. This will ony apply to yhe last relation if you join multiple like "groups.users"
	 * @return $this
	 */
	public function joinRelation($name, $selectAttributes = false, $type = 'INNER', $on = null) {		
		
		//replace existing
		$i = $this->relationIsJoined($name);
		if($i !== false){
			array_splice($this->joins, $i, 1);
		}

		$this->joins[] = ['relation', [
				'name' => $name,
				'type' => $type,
				'on' => Criteria::normalize($on),
				'selectAttributes' => $selectAttributes
		]];

		return $this;
	}
	
//	protected function getWhere() {
//		$where = parent::getWhere();
//		
//		foreach($where as $w) {
//			if(is_array($w) && is_array($w[2])) {
//				
//			}
//		}
//	}
//	
//	/**
//	 * Code for automatic join of relations based on the where table aliases.
//	 *
//	 * @todo move to ORM
//	 * @param string $relationParts
//	 */
//	private function joinWhereRelation($relationParts) {
//
//		$relationName = implode('.', $relationParts);
//		$alias = array_pop($relationParts);
//
//		if (!isset($this->aliasMap[$alias]) && $this->query->relationIsJoined($relationName) === false) {
//			$arName = $this->recordClassName;
//			if ($arName::getRelation($relationName)) {
//				IFW::app()->debug("Joining relation in from where() param: ".$relationName);
//				$this->query->joinRelation($relationName, false, 'LEFT');
//			}
//		}
//	}
//	
	/**
	 * Check if a relation was already joined
	 * @param string $name
	 * @return boolean|int false or index of join array. Do a strict check! $return === false;
	 */
	public function relationIsJoined($name) {
		for($i = 0, $c = count($this->joins);$i < $c; $i++) {
			if($this->joins[$i][0]=='relation' && $this->joins[$i][1]['name'] == $name) {
				return $i;
			}
		}
		return false;
	}
	
	private $recordClassName;
	
	public function setRecordClassName($recordClassName) {
		$this->recordClassName = $recordClassName;
		
		
		
		return $this->from($recordClassName::tableName());
		
	}
	
	public function createCommand() {
		
		if($this->getFetchMode() == null && isset($this->recordClassName)) {
			//set fetch mode to fetch Record objects
			$this->fetchMode(\PDO::FETCH_CLASS, $this->recordClassName, [false, $this->getAllowedPermissionTypes()]); //for new record
		}
		
		return parent::createCommand();
	}
	
	public function getRecordClassName() {
		return $this->recordClassName;
	}
	
	/**
	 * Get the query builder object that can build the select SQL statement
	 * 
	 * @param string $recordClassName
	 * @return QueryBuilder
	 */
	public function getBuilder() {		
		if(!isset($this->recordClassName)) {
			return parent::getBuilder();
		}
		return new QueryBuilder($this->recordClassName);
		
	}
	
	private $allowedPermissionTypes = [];
	
	/**
	 * Set permission type as allowed when querying records
	 * 
	 * Used by {@see IFW\Auth\Permissions\Model} to set that models returned have
	 * already been checked for read access.
	 * 
	 * @return static
	 */
	public function allowPermissionTypes(array $allowedPermissionTypes) {
		$this->allowedPermissionTypes = $allowedPermissionTypes;		
		return $this;
	}
	
	public function getAllowedPermissionTypes(){
		return $this->allowedPermissionTypes;
	}
	
	private $requirePermissionType;
	
	public function requirePermission($type) {
		$this->requirePermissionType = $type;		
	}
	
	public function getRequirePermission() {
		return $this->requirePermissionType;
	}
}