<?php
namespace IFW\Orm;

use Exception;
use IFW;
use IFW\Db\Command;
use IFW\Db\Criteria;
use IFW\Db\Query as DbQuery;
use PDO;

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
	
	/**
	 * Used for count queries where we need a copy of the query object but only do "select count(*) ..."
	 * 
	 * return self
	 */
	public function rejoinRelationsWithoutSelect() {
		for($i = 0, $c = count($this->joins);$i < $c; $i++) {
			if($this->joins[$i][0]=='relation') {
				$this->joins[$i][1]['selectAttributes'] = false;
			}
		}
		
		return $this;
	}
	
	private $recordClassName;
	
	
	/**
	 * Set the record class to query
	 * 
	 * Do not use this directly. This will be applied automatically by {@see Record::find()}
	 * 
	 * @param type $recordClassName
	 * @return self
	 */
	public function setRecordClassName($recordClassName) {
		$this->recordClassName = $recordClassName;
		return $this->from($recordClassName::tableName());
	}
	
	/**
	 * The record class to fetch
	 * 
	 * @return string
	 */
	public function getRecordClassName() {
		return $this->recordClassName;
	}
	
	/**
	 * Create a select command from this query
	 * 
	 * @return Command
	 */
	public function createCommand() {
		
		if($this->getFetchMode() == null && isset($this->recordClassName)) {
			//set fetch mode to fetch Record objects
			if(is_a($this->recordClassName, PropertyRecord::class, true)) {
				if($this->getRelationFromRecord() == null) {
					throw new \Exception($this->recordClassName.' is a property and can\'t be queried directly');
				}
			}
				
			$this->fetchMode(PDO::FETCH_CLASS, $this->recordClassName, $this->recordConstructorArgs); //for new record			
		}
		
		return parent::createCommand();
	}
	
	private $recordConstructorArgs = [false, [], []];
	
	
	/**
	 * Set values to be passed to the Record constructor
	 * 
	 * @param array $values
	 * @return self
	 */
	public function setValues(array $values) {
		$this->recordConstructorArgs[2] = $values;
		return $this;
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
		
		if(isset($this->requirePermissionType) && !$this->requirePermissionTypeHandled && !IFW::app()->getAuth()->user()->isAdmin()) {
			throw new Exception("A required permission type was given but the permission object doesn't support this.");
		}
		return new QueryBuilder($this->recordClassName);		
	}
	
//	private $allowedPermissionTypes = [];
	
	/**
	 * Set permission types as allowed when querying records
	 * 
	 * Used by {@see IFW\Auth\Permissions\Model} to set that models returned have
	 * already been checked for access.
	 * 
	 * You can use the PERMISSION_* constants or * for all permissions
	 * 
	 * ```
	 * (new Query)->allowPermissionTypes([\IFW\Auth\Permissions\Model::PERMISSION_READ]);
	 * ```
	 * 
	 * or
	 * 
	 * ```
	 * (new Query)->allowPermissionTypes(["*"]);
	 * ```
	 * 
	 * 
	 * @param string[] $allowedPermissionTypes An array of permission types defined in the constants.
	 * @return self
	 */
	public function allowPermissionTypes(array $allowedPermissionTypes) {
		$this->recordConstructorArgs[1] = $allowedPermissionTypes;		
		return $this;
	}
	
	/**
	 * Get the allowed permission types
	 * 
	 * These are set on the query object by {@see allowPermissionTypes()}
	 * 
	 * @return string[]
	 */
	public function getAllowedPermissionTypes(){
		return $this->recordConstructorArgs[1];
	}
	
	private $requirePermissionType;
	
	/**
	 * Used to check if the permissions model supports {@see requirePermissionType()}
	 * 
	 * @var bool 
	 */
	private $requirePermissionTypeHandled = false;
	
	/**
	 * Make the query filter only objects with this permission type
	 * 
	 * The permission object must call {@see getRequirePermision()} and handle it
	 * in (@see \IFW\Auth\Permissions\Model::internalApplyToQuery()}. Otherwise 
	 * an exception will be thrown when using this function while it's not supported.
	 * 
	 * @param string $type
	 * @return self	
	 */
	public function requirePermissionType($type) {
		$this->requirePermissionType = $type;		
		return $this;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getRequirePermissionType() {
		$this->requirePermissionTypeHandled = true;
		return $this->requirePermissionType;
	}
	
	protected function getAllowedClientMethods() {
		$methods = parent::getAllowedClientMethods();
		
		$methods[] = 'requirePermissionType';
		
		return $methods;
	}
	
	private $cacheHash = null;
	
	/**
	 * Enables caching of single records for the given hash
	 * The hash is a key value array ['dbField' => $value]
	 * 
	 * If repeated queries are made with the same hash then they are returned from cache in {@see Store::single()}
	 * 
	 * This is used in {@see Record::findByPk()} and {Relation::get()}
	 * 
	 * @param array $hash
	 */
	public function enableCache(array $hash) {
//		\IFW::app()->debug("Query enable cache: ".$key);
		$this->cacheHash = $hash;
	}
	
	
	public function getCacheHash() {		
		return $this->cacheHash;
	}
}