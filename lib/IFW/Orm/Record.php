<?php

namespace IFW\Orm;

use IFW\Util\DateTime;
use Exception;
use IFW;
use IFW\Auth\Permissions\AdminsOnly;
use IFW\Auth\Permissions\Model as PermissionsModel;
use IFW\Data\Model as DataModel;
use IFW\Db\Column;
use IFW\Db\Columns;
use IFW\Db\Exception\DeleteRestrict;
use IFW\Db\PDO;
use IFW\Exception\Forbidden;
use IFW\Imap\Connection;
use IFW\Util\ClassFinder;
use IFW\Util\StringUtil;
use IFW\Validate\ValidateUnique;

/**
 * Record model
 * 
 * Records are {@see DataModel}s that are stored in the database. Database columns are 
 * automatically converted into properties and relational data can be accessed
 * easily.
 *
 * Special columns
 * ---------------
 * 
 * 1. createdAt and modifiedAt and Datetime columns
 * 
 * Database columns "createdAt" and "modifiedAt" are automatically filled. They
 * should be of type "DATETIME" in MySQL. 
 *
 * All times should will stored in UTC. They are returned and set in the ISO 8601
 * Standard. Eg. 2014-07-22T16:10:15Z but {@see \DateTime} objects should be 
 * used to set dates.
 * 
 * 2. createdBy and modifiedBy
 *
 * Automatically set with the current userId.
 * 
 * 3. deleted
 * 
 * Enables soft delete functionality
 * 
 *
 * Basic usage
 * -----------
 *
 * <p>Create a new model:</p>
 * <code>
 * $user = new User();
 * $user->username="merijn";
 * $user->email="merijn@intermesh.nl";
 * $user->modifiedAt='2014-07-22T16:10:15Z'; //makes no sense but just for showing how to format the time.
 * $user->createdAt = new \DateTime(); //makes no sense but just for showing how to set dates.
 * $user->save();
 * </code>
 *
 * <p>Updating a model:</p>
 * <code>
 * $user = User::find(['username' => 'merijn'])->single();
 *
 * if($user){
 *    $user->email="merijn@intermesh.nl";
 *    $user->save();
 * }
 * </code>
 *
 * <p>Find all users ({@see find()}):</p>
 * <code>
 * $users = User::find();
 * foreach($users as $user){
 *     echo $user->username.'<br />';
 * }
 * </code>
 * 
 * 
 * Relations
 * ---------
 * 
 * The Record supports relational data. See the {@see defineRelations()} 
 * function on how to define them.
 * 
 * To get the "groups" relation of a user simply do:
 * 
 * <code>
 * 
 * //$user->groups returns a RelationStore because it's a has many relation
 * foreach($user->groups as $group){
 *    echo $group->name;
 * }
 * </code>
 * 
 * 
 * If you'd like to query a subset of the relation you can adjust the relation 
 * store's query object. You should clone the relation store because otherwise
 * you are adjusting the actual relation of the model that might be needed in 
 * other parts of the code:
 * 
 * <code>
 * $attachments = clone $message->attachments;
 * $attachments->getQuery()->where(['AND','!=', ['contentId' => null]]);
 * </code>
 * 
 * You can also set relations:
 * 
 * With models:
 * <code>
 * $user->groups = [$groupModel1, $groupModel2]; 
 * $user->save();
 * </code>
 * 
 * Or with arrays of attributes. (This is the API way when  posting JSON):
 * <code>
 * $user->groups = [['groupId' => 1]), ['groupId' => 2]]; 
 * $user->save();
 * </code>
 * 
 * Or modify relations directly:
 * <code>
 * $contact = Contact::findByPk($id);
 * $contact->emailAddresses[0]->type = 'work';
 * $contact->save();
 * </code>
 *
 * 
 * See also {@see RelationStore} for more information about how the has many relation collection works.
 *
 * See the {@see Query} object for available options for the find functions and the User object for an example implementation.
 * 
 * @param DataModel $permissions {@see getPermissions()}
 * 
 * @method static single() {@see Store::single()} For IDE autocomplete reasons this method is defined
 * @method static all() {@see Store::all()} For IDE autocomplete reasons this method is defined
 *
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class Record extends DataModel {
	
	use \IFW\Event\EventEmitterTrait;
	
	use \IFW\Data\ValidationTrait;
	
	/**
	 * Event fired in the save function.
	 * 
	 * Save can be cancelled by returning false or setting validation errors.
	 * 
	 * @param self $record
	 */
	const EVENT_BEFORE_SAVE = 1;	
	
	/**
	 * Fired after save
	 * 
	 * @param self $record
	 * @param boolean $success
	 * @param boolean $isNew $this->isNew() always returns false after save.
	 * @param array $modifiedAttributes Key value array of attributes before save. $this->isModified('attr') always returns false after save.
	 */
	const EVENT_AFTER_SAVE = 2;	
	
	/**
	 * Event fired in the delete function.
	 * 
	 * Delete can be cancelled by returning false.
	 * 
	 * @param self $record
	 */
	const EVENT_BEFORE_DELETE = 3;	
	
	/**
	 * Fired after delete
	 * 
	 * @param self $record
	 */
	const EVENT_AFTER_DELETE = 4;
	
	
	/**
	 * Fired on object construct 
	 * 
	 * @param self $record
	 */
	const EVENT_CONSTRUCT = 5;
	
	/**
	 * Fired when finding records
	 */
	const EVENT_FIND = 6;
	
	/**
	 * Fired when relations are defined
	 */
	const EVENT_DEFINE_RELATIONS = 7;
	
	/**
	 * Fires before validation
	 */
	const EVENT_BEFORE_VALIDATE = 8;
	
	/**
	 * Fires after validation
	 */
	const EVENT_AFTER_VALIDATE = 9;
	
	/**
	 * Fires when this record is converted into an array for the API
	 */
	const EVENT_TO_ARRAY = 10;
	
	
	/**
	 * The columns are stored per class
	 * 
	 * @var Columns 
	 */
	private static $columns = [];

	/**
	 * All relations are only fetched once per request and stored in this static
	 * array
	 *
	 * @var array
	 */
	public static $relationDefs;
	
	/**
	 * When this is set to true the model will be deleted on save.
	 * Useful for saving relations.
	 * 
	 * @var boolean
	 */
	public $markDeleted = false;

	/**
	 * Indiciates that the ActiveRecord is being contructed by PDO.
	 * Used in setAttribute so it skips fancy features that we know will only
	 * cause overhead.
	 *
	 * @var boolean
	 */
	private $loadingFromDatabase = true;

	/**
	 * True if this record doesn't exit in the database yet
	 * @var boolean
	 */
	private $isNew = true;

	/**
	 * Holds the accessed relations
	 * 
	 * Relations are accessed via __set and __get the values set or fetched there
	 * are stored in this array.
	 * 
	 * @var RelationStore[]
	 */
	private $relations = [];
	

	/**
	 * Tells us if this record is deleted from the database.
	 * 
	 * @var boolean 
	 */
	private $isDeleted = false;
	
	
	/**
	 * The permissions model
	 * 
	 * @var PermissionsModel 
	 */
	private $permissions;
	
	/**
	 * Set by PDO when this record was fetched via a relation
	 * 
	 * eg $contact->organizations
	 * 
	 * @access private
	 * @var boolean 
	 */
	protected $isRelational = false;
	
	/**
	 * Internally used to skip read permission check in the __construct
	 * {@see \IFW\Auth\Permissions\Model::query()}
	 * 
	 * @access private
	 * @var boolean
	 */
	protected $skipReadPermission = false;
	
	
		/**
	 * When saving relational this is set on the children of the parent object 
	 * that is  being saved. All objects in the save will not reset their 
	 * modifications until the parent save operation has been completed successfully.
	 * 
	 * Only then will resetModified() be called and it will bubble down the tree.
	 * 
	 * @var boolean 
	 */
	private $isSavedByRelation = false;
	
	/**
	 * When the record is saved this is set to true.
	 * Relational saves will prevent identical relations from being saved twice.
	 * 
	 * When a record is saved by a relation this flag will be true until commit or
	 * rollback is called.
	 * 
	 * @var boolean 
	 */
	private $isSaving = false;
	
	/**
	 * Keeps track if the save method started the transaction
	 * 
	 * @var boolean 
	 */
	private $saveStartedTransaction = false;
	
	
	/**
	 * Holds a copy of the attributes that were loaded from the database.
	 * 
	 * Used for modified checks.
	 * 
	 * @var array 
	 */
	private $oldAttributes = [];
	
	/**
	 * Constructor
	 * 
	 * It checks if the record is new or exisiting in the database. It also sets
	 * default attributes and casts mysql values to int, floats or booleans as 
	 * mysql values from PDO are always strings.
	 */
	public function __construct($isNew = true) {
		
		parent::__construct();

		$this->isNew = $isNew;

		if (!$this->isNew) {
			$this->castDatabaseAttributes();	//Will also call setOldAttributes()		
		} else {
			$this->setDefaultAttributes();
			$this->setOldAttributes();
		}
		
		$this->loadingFromDatabase = false;
		
		$this->init();
		
		if($this->isNew) {
//			Removed this check becuase it caused a problem with join relation. It creates a new object but it's a read action.
//			if(!$this->getPermissions()->can(PermissionsModel::ACTION_CREATE)) {
//				throw new Forbidden("You're not permitted to create a ".$this->getClassName());
//			}
		}else
		{
			//skipReadPermission is selected if you use IFW\Auth\Permissions\Model::query() so permissions have already been checked
			if(!$this->skipReadPermission && !$this->isRelational && !PermissionsModel::isCheckingPermissions() && !$this->getPermissions()->can(PermissionsModel::PERMISSION_READ)) {
				throw new Forbidden("You're not permitted to read ".$this->getClassName()." ".var_export($this->pk(), true));
			}
		}
		
		$this->fireEvent(self::EVENT_CONSTRUCT, $this);
		
	}
	
	/**
	 * This function is called at the end of the constructor.
	 * 
	 * You can set default attributes on new models here for example.
	 */
	protected function init() {
		
	}

	/**
	 * Mysql always returns strings. We want strict types in our model to clearly
	 * detect modifications
	 *
	 * @param array $columns
	 * @return void
	 */
	private function castDatabaseAttributes() {
		foreach ($this->getColumns() as $colName => $column) {			
			if(isset($this->$colName)) {
				$this->$colName = $column->dbToRecord($this->$colName);				
			}
		}		
		
		//filled by joined relations
		foreach($this->relations as $relationName => $relationStore) {

			//check loading from database boolean to prevent infinite loop because 
			//the reverse / parent relations are set automatically.
			if($relationStore[0]->loadingFromDatabase) {
				$relationStore[0]->loadingFromDatabase = false;
				$relationStore[0]->castDatabaseAttributes();
				$relationStore[0]->isNew = false;
			}			
		}
		
		$this->setOldAttributes();
	}
	
	/**
	 * Set's current column values in the oldAttributes array
	 */
	private function setOldAttributes() {
		foreach ($this->getColumns() as $colName => $column) {			
			if(isset($this->$colName)) {
				$this->$colName = $this->oldAttributes[$colName] = $this->$colName;				
			}else
			{
				$this->oldAttributes[$colName] = null;
			}
		}
	}

	/**
	 * Clears all modified attributes
	 */
	public function clearModified() {
		foreach ($this->getColumns() as $colName => $column)
			$this->oldAttributes[$colName] = null;
	}

	/**
	 * Set's the default values from the database definitions
	 * 
	 * Also sets the 'createdBy' column to the current logged in user id.
	 */
	protected function setDefaultAttributes() {
		
		foreach ($this->getColumns() as $colName => $column) {			
			$this->$colName = $column->default;			
		}
		
		if ($this->hasColumn('createdBy')) {
			$this->createdBy = IFW::app()->getAuth()->user() ? IFW::app()->getAuth()->user()->id() : 1;
		}
	}

	/**
	 * Define events is called by {@see \IFW\Event\EventEmitterTrait}
	 */
	public static function defineEvents () {		
	
	}

	/**
	 * Return the table name to store these records in.
	 *
	 * By default it removes the first two parts ofr the namespace and the second last namespace which is "DataModel".
	 *
	 * @return string
	 */
	public static function tableName() {
		return self::classToTableName(get_called_class());
	}

	/**
	 * Get the table name from a record class name
	 * 
	 * @param string $class
	 * @return string
	 */
	protected static function classToTableName($class) {
		
		$cacheKey = 'tableName-'.$class;
		if(($tableName = IFW::app()->getCache()->get($cacheKey))) {
			return $tableName;
		}
		
		$parts = explode("\\", $class);		
		//remove GO\Core or GO\Modules
		if($parts[1] == 'Modules') {
			$parts = array_slice($parts, 3); //Strip GO\Modules\VendorName		
		}else
		{
			$parts = array_slice($parts, 2);		
		}
		//remove "Model" part
		array_splice($parts, -2, 1);
		
		$tableName = StringUtil::camelCaseToUnderscore(implode('', $parts));
		
		IFW::app()->getCache()->set($cacheKey, $tableName);
		
		return $tableName;
	}
	

	/**
	 * Get the database columns
	 *
	 * <p>Example:</p>
	 * <code>
	 * $columns = User::getColumns();
	 * </code>
	 *
	 * @return Columns|Column[] Array with column name as key
	 */
	public static function getColumns() {
		
		$calledClass = get_called_class();
		
		if(!isset(self::$columns[$calledClass])) {
			self::$columns[$calledClass] = new Columns(static::tableName());
		}
		
		return self::$columns[$calledClass];
	}

	/**
	 * Get the database column definition
	 *
	 * <p>Example:</p>
	 * <code>
	 * $column = User::getColumn('username);
	 * echo $column->length;
	 * </code>
	 *
	 * @param string $name
	 * @return Column
	 */
	public static function getColumn($name) {
		$c = self::getColumns();

		return isset($c[$name]) ? $c[$name] : null;
	}
	
	/**
	 * Checks if a column exists
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public static function hasColumn($name) {
		return static::getColumn($name) !== null;
	}

	/**
	 * The primary key columns
	 * 
	 * This value is auto detected from the database. 
	 *
	 * @return string[] eg. ['id']
	 */
	public static function getPrimaryKey() {
		
		$cacheKey = static::class.'::pk';
		
		$pk = IFW::app()->getCache()->get($cacheKey);
		
		if(!$pk) {
			$pk = [];
			foreach(self::getColumns() as $column) {
				
				if($column->primary) {				
					$pk[] = $column->name;
				}
			}
			
			if(empty($pk)) {
				IFW::app()->debug("WARNING: No primary key defined for ".self::getClassName()." database table: ".self::tableName());
			}
			
			IFW::app()->getCache()->set($cacheKey, $pk);
		}
		
		return $pk;
	}
	
	/**
	 * Get the primary key values.
	 * 
	 * ``````````````
	 * ['id' => 1]
	 * ``````````````
	 * 
	 * @return string[] Key value array
	 */
	public function pk() {
		$primaryCols = $this->getPrimaryKey();
		
		$pk = [];
		
		foreach($primaryCols as $colName) {
			$pk[$colName] = $this->$colName;
		}
		
		return $pk;		
	}

	/**
	 * Returns true if this is a new record and does not exist in the database yet.
	 *
	 * @return boolean
	 */
	public function isNew() {

		return $this->isNew;
	}


	/**
	 * The special magic getter
	 *
	 * This function finds database values and relations
	 * 
	 * Avoid naming conflicts. The set order is:
	 * 
	 * 1. get function
	 * 2. column
	 * 3. extra selected value in sql query
	 * 4. relation
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {	
		$getter = 'get'.$name;

		if(method_exists($this,$getter)){
			return $this->$getter();
		} elseif (($relation = $this->getRelation($name))) {
			return $this->getRelated($name);
		} else {
			throw new Exception("Can't get not existing property '$name' in '".static::class."'");
		}
	}

	/**
	 * Get's a relation from cache or from the database
	 *
	 * @param string $name Name of the relation
	 * @param Query $query
	 * @return \IFW\Orm\RelationStore | self | null Returns null if not found
	 */
	protected function getRelated($name) {
		
//		IFW::app()->debug("Getting relation ".$name);
		
		
		//_isRelational is set by the query object in getRelated
//		if($this->isRelational && !PermissionsModel::isCheckingPermissions()){
//			IFW::app()->debug($name);
//			if(!$this->getPermissions()->can(PermissionsModel::PERMISSION_READ)) {
//				IFW::app()->debug("Relation ".$name." not returned because this record (".$this->getClassName().") is not readable for current user. It's fetched by relation.");
//				return null;
//			}
//		}

		$relation = $this->getRelation($name);

		if (!$relation) {
			throw new Exception($name . ' is not a relation of ' . static::class);
		}
		
		if(!isset($this->relations[$name])){			
			
			//Get't RelationStore
			$store = $relation->get($this);			
			
			//Apply permissions to relational query
			$toRecordName = $relation->getToRecordName();		
			$permissions = $toRecordName::internalGetPermissions();
			$permissions->setRecordClassName($toRecordName);
			$permissions->applyToQuery($store->getQuery());
			
			$this->relations[$name] = $store;		
		}

		
		if($relation->hasMany()) {
			return $this->relations[$name];
		}else
		{
			$record = $this->relations[$name]->single();
			if($record) {
				return $record;
			}else
			{
				return null;
			}
		}		
	}	
	
	/**
	 *
	 * {@inheritdoc}
	 * 
	 */
	public function __isset($name) {
		return ($this->getRelation($name) && $this->getRelated($name)) ||
						parent::__isset($name);
	}
	
	/**
	 * Checks if a relation has already been fetched or set in the record.
	 * 
	 * @param string $name Relation name
	 * @return bool
	 */
	public function relationIsFetched($name) {
		return array_key_exists($name, $this->relations);
	}
	
	/**
	 * Check if a readable propery exists
	 * 
	 * public properties, getter methods, columns and relations are checked
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasReadableProperty($name) {
		if($this->getRelation($name)) {
			return true;
		}
		
		return parent::hasReadableProperty($name);
	}
	
	/**
	 * Check if a writable propery exists
	 * 
	 * public properties, setter methods, columns and relations are checked
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasWritableProperty($name) {
		
		if($this->getRelation($name)) {
			return true;
		}
		return parent::hasWritableProperty($name);
	}
	
	public function setValues(array $properties) {
		
		//convert client input. For example date string to Datetime object.
		foreach(self::getColumns() as $name => $column) {
			if(isset($properties[$name])){
				$properties[$name]=$column->normalizeInput($properties[$name]);
			}
		}
		
		return parent::setValues($properties);
	}

	/**
	 * Magic setter. Set's database columns or setter functions.
	 * 
	 * Avoid naming conflicts. The set order is:
	 * 
	 * 1. set function
	 * 2. column
	 * 3. extra selected value in sql query
	 * 4. relation
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {	
		
		if($this->setJoinedRelationAttribute($name, $value)) {
			return;
		}
			
		$setter = 'set'.$name;

		if(method_exists($this,$setter)){
			$this->$setter($value);
		} elseif (($relation = $this->getRelation($name))) {												
			$this->setRelated($name, $value);
		} else {
			$getter = 'get' . $name;
			if(method_exists($this, $getter)){
				
				//Allow to set read only properties with their original value.
				//http://stackoverflow.com/questions/20533712/how-should-a-restful-service-expose-read-only-properties-on-mutable-resources								
//				$errorMsg = "Can't set read only property '$name' in '".static::class."'";
				//for performance reasons we simply ignore it.
				\IFW::app()->getDebugger()->debug("Discarding read only property '$name' in '".static::class."'");
			}else {
				$errorMsg = "Can't set not existing property '$name' in '".static::class."'";
				throw new Exception($errorMsg);
			}
		}

	}
	
	private function setJoinedRelationAttribute($name, $value) {
		if(!$this->loadingFromDatabase || !strpos($name, '@')) {
			return false;
		}
		
		$propPathParts = explode('@', $name);		
		$propName = array_pop($propPathParts);
		
		$currentRecord = &$this;				
		foreach ($propPathParts as $part) {		
			$relation = $currentRecord::getRelation($part);
			if($relation && !$currentRecord::relationIsFetched($part)) {
				$cls = $relation->getToRecordName();
				$record = new $cls(true);
				$currentRecord->$part = $record;
			}

			$currentRecord = &$currentRecord->$part;
			
		}
		$currentRecord->loadingFromDatabase = true; //swichted back in castDatabaseAttributes()
		$currentRecord->$propName = $value;			
	
		return true;
	}
	
	/**
	 * Set's a relation
	 * 
	 * Useful when you want to do something extra when setting a relation. Override
	 * it with a setter function and use this function inside.
	 * 
	 * @example
	 * ```````````````````````````````````````````````````````````````````````````
	 * public function setExampleRelation($value) {
	 *		$this->vatRate = $vatCode->rate;
	 *		$store = $this->setRelated('exampleRelation', $value);
	 * 
	 *		$this->somePropToCopy = $store[0]->theCopiedProp;
	 *	}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return RelationStore
	 */
	protected function setRelated($name, $value) {
		
		$relation = $this->getRelation($name);
		//set to null to prevent loops when setting parent relations. 
		//The relationIsFetched will work within the __set operation this way.
		$this->relations[$name] = null; 
		$this->relations[$name] = $relation->set($this, $value);				
		
		return $this->relations[$name];
	}
	

	/**
	 * Check if this record or record attribute has modifications not saved to
	 * the database yet.
	 * 
	 * <code>
	 * 
	 * if($record->isModified()) {
	 *	//the record has at least one modified attribute
	 * }
	 * 
	 * if($record->isModified('foo')) {
	 *	//the attribute foo has been modified
	 * }
	 * 
	 * if($record->isModified(['foo','bar'])) {
	 *	//foo or bar is modified
	 * }
	 * 
	 * </code>
	 *
	 * @param string|array $attributeOrRelationName If you pass an array then they are all checked
	 * @return boolean
	 */
	public function isModified($attributeOrRelationName = null) {
		if (!isset($attributeOrRelationName)) {
			
			foreach($this->oldAttributes as $colName => $loadedValue) {
				//do not check stict here as it leads to date problems.
				if($this->$colName != $loadedValue)
				{
					return true;
				}
			}
			
			foreach($this->relations as $store) {
				if($store->isModified()) {
					return true;
				}
			}
			
			return false;
		}
		
		if (!is_array($attributeOrRelationName)) {
			$attributeOrRelationName = [$attributeOrRelationName];
		}
		foreach ($attributeOrRelationName as $a) {						
			
			if($this->getColumn($a)) {				
				if(!isset($this->oldAttributes[$a]) && isset($this->a)) {
					return true;
				}
				
				if($this->oldAttributes[$a] != $this->$a) {
					return true;
				}
			}elseif($this->getRelation($a)) {
				if(isset($this->relations[$a]) && $this->relations[$a]->isModified()) {
					return true;
				}
			} else
			{
				throw new \Exception("Not an attribute or relation '$a'");
			}
		}
		return false;
	}
	
	/**
	 * Get the modified attributes and relation names.
	 * 
	 * See also {@see getModifiedAttributes()}
	 * 
	 * @return string[]
	 */
	public function getModified() {
		$props = array_keys($this->getModifiedAttributes());
	
		foreach($this->relations as $r) {
			if($r->isModified()){
				$props[] = $r->getRelation()->getName();
			}
		}
		
		return $props;
	}
	

	/**
	 * Reset record or specific attribute(s) or relation to it's original value and 
	 * clear the modified attribute(s).
	 *
	 * @param string|array|null $attributeName
	 */
	public function reset($attributeName = null) {
		
		if(!isset($attributeName)) {
			$attributeName = array_keys($this->oldAttributes);
			
			$this->relations = [];
			
		}else if(!is_array($attributeName)) {
			$attributeName = [$attributeName];
		}
		
		foreach($attributeName as $a) {
			if(array_key_exists($a, $this->oldAttributes)) {
				$this->$a = $this->oldAttributes[$a];
			} else if (isset($this->relations[$a])) {
				unset($this->relations[$a]);
			} else
			{
				throw new Exception("Attribute or relation '$a' not found!");
			}
		}
	}

	/**
	 * Get the old value for a modified attribute.
	 *
	 * <p>Example:</p>
	 * <code>
	 *
	 * $model = User::findByPk(1);
	 * $model->username='newValue':
	 *
	 * $oldValue = $model->getOldAttributeValue('username');
	 *
	 * </code>
	 * @param string $attributeName
	 * @return mixed
	 */
	protected function getOldAttributeValue($attributeName) {
		
//		if(!$this->isModified($attributeName)) {
//			throw new \Exception("Can't get old attribute value because '$attributeName' is not modified");
//		}
		return $this->oldAttributes[$attributeName];
	}

	/**
	 * Get modified attributes
	 *
	 * Get a key value array of modified attribute names with their old values
	 * that are not saved to the database yet.
	 *
	 * <p>Example:</p>
	 * <code>
	 *
	 * $model = User::findByPk(1);
	 * $model->username='newValue':
	 *
	 * $modifiedAttributes = $model->getModifiedAttributes();
	 * 
	 * $modifiedAtttibutes = ['username' => 'oldusername'];
	 *
	 * </code>
	 *
	 * @return array eg. ['attributeName' => 'oldValue]
	 */
	public function getModifiedAttributes() {
		$modified = [];
		
		foreach($this->oldAttributes as $colName => $loadedValue) {
			if($this->$colName != $loadedValue) {
				$modified[$colName] = $loadedValue;
			}
		}
		return $modified;
	}

	/**
	 * Define relations for this or other models.
	 * 
	 * You can use the following functions:
	 * 
	 * * {@see hasOne()}
	 * * {@see hasMany()}
	 *
	 * <p>Example:</p>
	 * <code>
	 * public static function defineRelations(){
	 *	
	 *  self::hasOne('owner', User::class, ['ownerUserId' => 'id]);
	 
	 *	self::hasMany('emailAddresses', ContactEmailAddress::class, ['id' => 'contactId']);
	 *	
	 *	self::hasMany('tags', Tag::class, ['id' => 'contactId'])
	 *			->via(ContactTag::class, ['tagId' => 'id']);
	 *	
	 *	self::hasOne('customfields', ContactCustomFields::class, ['id' => 'id']);
	 * }
	 * </code>
	 * 
	 * It's also possible to add relations to other models:
	 * 
	 * <code>
	 * public static function defineRelations(){
	 *  
	 *	GO\Core\Auth\DataModel\User::hasOne('contact', Contact::class, ['id' => 'userId']);
	 *	
	 * }
	 * </code>
	 */
	protected static function defineRelations() {
		
//		self::fireStaticEvent(self::EVENT_DEFINE_RELATIONS);
	}
	
	
	/**
	 * When a relation is set we attempt to set the parent relation. in
	 * {@see RelationStore::setParentRelation()}
	 * 
	 * @example 
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * $contact = new Contact();
	 *		
	 *		$emailAddress = new EmailAddress();
	 *		$emailAddress->email = 'test@intermesh.nl';
	 *		$emailAddress->type = 'work';
	 *		
	 *		$contact->emailAddresses[] = $emailAddress;
	 *		
	 * //these are equal because of this functionality
	 *		$this->assertEquals($emailAddress->contact, $contact);
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param Relation $childRelation
	 * @return Relation
	 */
	
	public static function findParentRelation(Relation $childRelation) {
		if($childRelation->getViaRecordName()) {
			//not supported
			return null;
		}
		
		foreach(self::getRelations() as $parentRelation) {
			if(
							!$parentRelation->hasMany() && 
							$parentRelation->getToRecordName() == $childRelation->getFromRecordName() && 
							self::keysMatch($childRelation, $parentRelation)
				) {
				
//				GO()->debug($this->getClassName().'::'.$relation->getName().' set by parent '.$parentRecord->getClassName());
				return $parentRelation;
			}							
		}
		
		return null;
		
	}
	
	
	private static function keysMatch(Relation $parentRelation, Relation $childRelation) {
		
		$childKeys = $childRelation->getKeys();
		
		if(count($parentRelation->getKeys()) != count($childKeys)) {
			return false;
		}
				
		//check if keys are reversed
		foreach($parentRelation->getKeys() as $from => $to) {
			if(!isset($childKeys[$to])) {
				return false;
			}
			
			if($childKeys[$to] != $from) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Get's the relation definition
	 *
	 * @param string $name
	 * @return Relation
	 */
	public static function getRelation($name) {
		
		$map = explode('.', $name);
		
		$modelName = static::class;
		
		foreach($map as $name) {
			$relations = $modelName::getRelations();
			if(!isset($relations[$name])) {
				return false;
			}
			$modelName = $relations[$name]->getToRecordName();
		}
		
		return $relations[$name];
	}

	/**
	 * Get all relations names for this model
	 *
	 * @return Relation[]
	 */
	public static function getRelations() {		
		$calledClass = static::class;
		
		if(!isset(self::$relationDefs[$calledClass])){
			self::$relationDefs[$calledClass] = IFW::app()->getCache()->get($calledClass.'-relations');
		}

		return self::$relationDefs[$calledClass];
	}
	
	
	/**
	 * Called from {@see \IFW\App::init()}
	 * 
	 * Calls defineRelations on all models
	 */
	public static function initRelations() {
		
		if(IFW::app()->getCache()->get('initRelations')) {
			return true;
		}
		
		IFW::app()->debug("Initializing Record relations");
			
		self::$relationDefs = [];

		foreach(\IFW::app()->getModules() as $module) {

			$classFinder = new ClassFinder();		
			$classFinder->setNamespace($module::getNamespace());

			$classes = $classFinder->findByParent(self::class);
			
			foreach($classes as $className) {	
				
				if(!isset(self::$relationDefs[$className])) {
					self::$relationDefs[$className] = [];
				}

				$className::defineRelations();
			}
		}
		
		foreach(self::$relationDefs as $className => $defs) {
			IFW::app()->getCache()->set($className.'-relations', $defs);
		}
		
		IFW::app()->getCache()->set('initRelations', true);
	}

	/**
	 * Save changes to database
	 *
	 * <p>Example:</p>
	 * <code>
	 * $model = User::findByPk(1);
	 * $model->setAttibutes(['username'=>'admin']);
	 * if(!$model->save())	{
	 *  //oops, validation must have failed
	 *   var_dump($model->getValidationErrors();
	 * }
	 * </code>
	 * 
	 * Don't override this method. Override {@see internalSave()} instead.
	 *
	 * @return bool
	 */
	public final function save() {		
		
		
		if($this->isSaving) {
			return true;
		}
		
//		GO()->debug("Save ".$this->getClassName(), 'general', 1);
		
		$this->isSaving = true;
		$success = false;
		try{
			
					
			if($this->markDeleted) {
				$this->isSaving = false;
				return $this->delete();
			}
		
			$action = $this->isNew() ? PermissionsModel::PERMISSION_CREATE : PermissionsModel::PERMISSION_UPDATE;

			if(!$this->getPermissions()->can($action)) {
				$this->isSaving = false;
				throw new Forbidden("You're (user ID: ".IFW::app()->getAuth()->user()->id().") not permitted to ".$action." ".$this->getClassName()." ".var_export($this->pk(), true));
			}
			
			if (!$this->validate()) {
				$this->isSaving = false;
				return false;
			}
			
			//don't start new transaction if we're already in one
			//we start it before validation because you might want to override 
			//internalValidate() to create some required relations for example.
			$this->saveStartedTransaction = !$this->getDbConnection()->inTransaction();
			if($this->saveStartedTransaction) {
				$this->getDbConnection()->beginTransaction();
			}	

			//save modified attributes for after save event
			$success = $this->internalSave();			
			if(!$success) {
				\IFW::app()->debug(static::class.'::internalSave returned '.var_export($success, true));
			}

			if(!$this->fireEvent(self::EVENT_AFTER_SAVE, $this, $success)){			
				$success = false;
			}			
			return $success;			
			
		} finally {
			if(!$success) {
				\IFW::app()->debug("Save of ".$this->getClassName()." failed. Validation errors: ".var_export($this->getValidationErrors(), true));
				$this->rollBack();								
			}else {			
				if(!$this->isSavedByRelation) {
					$this->commit();
				}
			}
		}
	}
	
	/**
	 * Rollback changes and database transaction after failed save operation
	 */
	protected function rollBack() {
		if($this->saveStartedTransaction) {
			$this->getDbConnection()->rollBack();
			$this->saveStartedTransaction = false;
		}
		if($this->isNew()) {
			//rollback auto increment ID too
			$aiCol = $this->findAutoIncrementColumn();
			if($aiCol) {
				$this->{$aiCol->name} = null;
			}
		}		
		$this->isSaving = false;
	}
	
	/**
	 * Clears the modified state of the object and commits database transaction 
	 * after successful save operation.
	 */
	private function commit() {
		if($this->saveStartedTransaction) {
			$this->getDbConnection()->commit();
			$this->saveStartedTransaction = false;
		}
		
		$this->isNew = false;
		$this->setOldAttributes();
		$this->isSavedByRelation = false;
		$this->isSaving = false;
		
		//Unset the accessed relations so user set relations are queried from the db after save.
		foreach($this->relations as $relationName => $relationStore) {
			foreach($relationStore as $record) {
				if(!is_a($record, self::class)) {					
					GO()->debug($relationStore);
					throw new \Exception("Not a record in ".$this->getClassName()."::".$relationName."?");
				}
				if($record->isSaving && !$this->isSavedByRelation) {
					$record->commit();
				}
			}
			$relationStore->reset();
		}
		$this->relations = [];		
	}	
	
	/**
	 * Performs the save to database after validation and permission checks.
	 * 
	 * After this function the modified attributes are reset and the isNew() function 
	 * will return false.
	 * 
	 * If you want to add functionality before or after save then override this 
	 * method. This method is executed within a database transaction and after 
	 * validation and permission checks so you don't have to worry about that.
	 * 
	 * @return boolean
	 * @throws Exception
	 */
	protected function internalSave() {
		
		
		if(!$this->fireEvent(self::EVENT_BEFORE_SAVE, $this)){
			return false;
		}
		
		if(!$this->saveBelongsToRelations()) {
			return false;
		}
		
		if ($this->isNew) {
			if(!$this->insert()){
				throw new \Exception("Could not insert record into database!");
			}
		} else {
			if(!$this->update()){
				throw new \Exception("Could not update record into database!");
			}
		}
		
		if(!$this->saveRelations()) {		
			return false;
		}
		
		
		return true;
	}
	
	/**
	 * Relations that are saved after this record
	 * 
	 * @return boolean
	 */
	private function saveRelations() {
		foreach($this->relations as $relationName => $relationStore) {			
			if(!isset($relationStore)) {
				continue;
			}
			
			$relation = $this->getRelation($relationName);
			if($relation->isBelongsTo()) {
				continue;
			}
			
			if(!$relationStore->isModified()) {					
				continue;
			}
			
			//this will prevent modifications to be cleared
			foreach($relationStore as $record) {
				//don't set this if the record was already saving. Loops.
				if(!$record->isSaving) {
					$record->isSavedByRelation = true;
				}
			}

			if(!$relationStore->save()) {				
				$this->setValidationError($relationName, 'relation');				
				return false;
			}
		}
		
		return true;
	}	
	
	/**
	 * Belongs to relations that have been set must be saved before saving this record.
	 * 
	 * @return boolean
	 */
	protected function saveBelongsToRelations() {
		
		foreach($this->relations as $relationName => $relationStore) {
			
			if(!isset($relationStore)) {
				continue;
			}
			
			$relation = $this->getRelation($relationName);
			
			if(!$relation->isBelongsTo()) {
				continue;
			}
			
			if(!$relationStore->isModified()) {
				/*
				 * Update keys. Because the relation might not be modified anymore but could have been new at the time the relation was set.
				 * 
				 * eg.
				 * 
				 * $record = new A();
				 * 
				 * $belongsTo = new B();
				 * $record->belongsTo = $belongsTo; //$record can not get key of $belongsTo yet.
				 * 
				 * $belongsTo->save(); //NOt it gets a key but $record is not aware yet.
				 * 
				 * $record->save(); //Now we get into this code block here and keys are set
				 */
				
				$relationStore->setNewKeys();
				continue;
			}
//			
//			IFW::app()->debug("Saving belongs to relation ".$this->getClassName().'::'.$relationName);
			
			//Modifications are not cleared directly.
			foreach($relationStore as $record) {
				//don't set this if the record was already saving. Loops.
				if(!$record->isSaving) {
					$record->isSavedByRelation = true;
				}
			}
			
			if(!$relationStore->save()) {						
				$this->setValidationError($relationName, 'relation');
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Can be used to check if this record is saved directly or by a parent relation.
	 * 
	 * @return boolean
	 */
	protected function isSavedByRelation() {
		return $this->isSavedByRelation;
	}

	/**
	 * Inserts the model into the database
	 *
	 * @return boolean
	 */
	private function insert() {

		if ($this->hasColumn('createdAt') && empty($this->createdAt)) {
			$this->createdAt = new DateTime();
		}

		if ($this->hasColumn('modifiedAt') && !$this->isModified('modifiedAt')) {
			$this->modifiedAt = new DateTime();
		}
		
		if ($this->hasColumn('modifiedBy') && !$this->isModified('modifiedBy')) {
			$this->modifiedBy = IFW::app()->getAuth()->user() ? IFW::app()->getAuth()->user()->id() : 1;
		}

		$colNames = [];
		$bindParams = [];
		
		//Build an array of fields that are set in the object. Unset columns will
		//not be in the SQL query so default values from the database are respected.
		$i = 0;
		foreach ($this->getColumns() as $colName => $col) {
			$i++;
			$colNames[':attr'.$i] = $colName;				
			$bindParams[':attr'.$i] = $col->recordToDb($this->$colName);
		}

		$sql = "INSERT INTO `{$this->tableName()}` (`" . implode('`,`', array_values($colNames)) . "`) VALUES " .
						"(" . implode(', ', array_keys($colNames)) . ")";

		try {
			//find auto increment column first because it might do a show tables query
			$aiCol = $this->findAutoIncrementColumn();
			
			$stmt = $this->getDbConnection()->getPDO()->prepare($sql);
			
			foreach ($colNames as $bindTag => $colName) {
				$column = $this->getColumn($colName);
				$stmt->bindValue($bindTag, $column->recordToDb($this->$colName), $column->pdoType);
			}

			IFW::app()->getDebugger()->debugSql($sql, $bindParams);

			$ret = $stmt->execute();
			
			
		} catch (Exception $e) {

			$msg = $e->getMessage();

			$msg .= "\n\nFull SQL Query: " . $sql . "\n\nParams:\n" . var_export($bindParams, true);

			$msg .= "\n\n" . $e->getTraceAsString();

			
			throw new Exception($msg);
		}
		
		if($aiCol) {
			$lastInsertId = intval($this->getDbConnection()->getPDO()->lastInsertId());			
//			IFW::app()->debug("Last insert ID for ".$this->getClassName().": ".$lastInsertId);			
			
			if(empty($lastInsertId)) {
				throw new \Exception("Auto increment column didn't increment!");
			}
			$this->{$aiCol->name} = $lastInsertId;
		}

		return $ret;
	}
	
	/**
	 * 
	 * @return Column
	 */
	private function findAutoIncrementColumn() {
		foreach($this->getColumns() as $col) {
			if($col->autoIncrement) {
				return $col;
			}
		}
		
		return false;
	}

	/**
	 * Updates the database with modified attributes.
	 * 
	 * You generally don't use this function yourself. The only case it might be
	 * useful if you want to generate some attribute based on the auto incremented
	 * primary key value. For an order number for example.
	 * 
	 * @return boolean
	 * @throws Exception
	 */
	protected function update() {		

		//commented out the modifiedAt existance check. If we do this then we'll log to much when importing and applying no changes.
//		if (!$this->isModified() && !$this->hasColumn('modifiedAt') && !$this->hasColumn('modifiedBy')) {			
		if (!$this->isModified()) {			
			return true;
		}
		
		if ($this->getColumn('modifiedAt') && !$this->isModified('modifiedAt')) {
			$this->modifiedAt = new \DateTime();
		}
		
		if ($this->getColumn('modifiedBy') && !$this->isModified('modifiedBy')) {
			$this->modifiedBy = IFW::app()->getAuth()->user() ? IFW::app()->getAuth()->user()->id() : 1;
		}		
		
		$modifiedAttributeNames = array_keys($this->getModifiedAttributes());
		
		if(empty($modifiedAttributeNames))
		{
			return true;
		}
		
		$i =0;		
		$tags = [];
		$updates = [];
		
		foreach ($modifiedAttributeNames as $colName) {
			$i++;
			$tag = ':attr'.$i;
			$updates[] = "`$colName` = " . $tag;
			
			$tags[$tag] = ['colName' => $colName, 'isPk' => false];
		}

		$sql = "UPDATE `{$this->tableName()}` SET " . implode(',', $updates) . " WHERE ";

		$bindParams = [];
		
		$pks = $this->getPrimaryKey();
		
		$first = true;
		foreach ($pks as $colName) {				
			$i++;				
			$tag = ':attr'.$i;

			if (!$first){
				$sql .= ' AND ';
			}else{
				$first = false;
			}

			$sql .= "`" . $colName . "`= " . $tag;				
			$tags[$tag] = ['colName' => $colName, 'isPk' => true];
		}

		try {
			$stmt = $this->getDbConnection()->getPDO()->prepare($sql);

			foreach ($tags as $tag => $attr) {				
				$column = $this->getColumn($attr['colName']);			
				
				//if it's a primary key and it's modified we must bind the old value here
				$value = $attr['isPk'] && !$this->isNew && $this->isModified($attr['colName']) ? $this->getOldAttributeValue($attr['colName']) : $this->{$attr['colName']};
				
				$value = $column->recordToDb($value);
				
				$bindParams[$tag] = $value;				
				$stmt->bindValue($tag, $value, $column->pdoType);				
			}
			IFW::app()->getDebugger()->debugSql($sql, $bindParams);

			$ret = $stmt->execute();
		} catch (Exception $e) {
			$msg = $e->getMessage();

			if (IFW::app()->getDebugger()->enabled) {
				$msg .= "\n\nFull SQL Query: " . $sql . "\n\nParams:\n" . var_export($bindParams, true);

				$msg .= "\n\n" . $e->getTraceAsString();

				IFW::app()->debug($msg);
			}
			throw $e;
		}
		
		return $ret;
	}

	/**
	 * Find a Record by primary key
	 *
	 * <p>Example:</p>
	 * <code>
	 * $user = User::findByPk(1);
	 * </code>
	 * 
	 * The primary key can also be an array:
	 * 
	 * <code>
	 * $user = User::find(['groupId'=>1,'userId'=>2])->single();
	 * </code>
	 *
	 * @param int|array $pk
	 * @return static
	 */
	public static function findByPk($pk) {
		if (!is_array($pk)) {
			$pk = [static::getPrimaryKey()[0] => $pk];
		}
		
		$query = new Query();
		$query->where($pk);
		
		return self::find($query)->single();
	}

	/**
	 * Find records
	 * 
	 * Finds records based on the {@see Query} Object you pass. It returns a
	 * {@see Store} object. The documentation tells that it returns an instance
	 * of this model but that's just to enable autocompletion.
	 * 
	 * Basic usage
	 * -----------
	 * 
	 * <code>
	 * 
	 * //Single user by attributes.
	 * $user = User::find(['username' => 'admin'])->single(); 
	 * 
	 * //Multiple users with search query.
	 * $users = User::find(
	 *         (new Query())
	 *           ->orderBy([$orderColumn => $orderDirection])
	 *           ->limit($limit)
	 *           ->offset($offset)
	 *           ->searchQuery($searchQuery, ['t.username','t.email'])
	 *         );
	 * 
	 * foreach ($users as $user) {
	 *   echo $user->username."<br />";
	 * }
	 * </code>
	 * 
	 * Join relations
	 * --------------
	 * 
	 * With {@see Query::joinRelation()} it's possible to join a relation so that later calls to that relation don't need to be fetched from the database separately.
	 * 
	 * <code>
	 * $contacts = Contact::find(
	 *         (new Query())
	 *           ->joinRelation('addressbook', true)
	 *         );
	 * 
	 * foreach ($contacts as $contact) {
	 *   echo $contact->addressbook->name."<br />"; //no query needed for the addressbook relation.
	 * }
	 * </code>
	 * 
	 * Complex join {@see Query::join()}
	 * ------------
	 * <code>
	 * 
	 * $groups = Group::find((new Query())
	 *         ->orderBy([$orderColumn => $orderDirection])
   *         ->limit($limit)
   *         ->offset($offset)
   *         ->search($searchQuery, ['t.name'])
   *         ->join(
   *              UserGroup::class,
	 *              'userGroup',
   *              (new Criteria())
   *                  ->where('t.id = userGroup.groupId')
   *                  ->andWhere(["userGroup.userId", $userId])
   *              ,    
   *              'LEFT')
   *          ->where(['userGroup.groupId'=>null])
   *          );
	 * </code>
	 * 
	 * More features
	 * -------------
	 * 
	 * <code>
	 * $finder = Contact::find(
						(new Query())
								->select('t.*, count(emailAddresses.id)')
								->joinRelation('emailAddresses', false)								
								->groupBy(['t.id'])
								->having("count(emailAddresses.id) > 0")
						->where(['!=',['lastName'=>null]])
						->andWhere((new Criteria())
							->where(['firstName', => ['Merijn', 'Wesley']]) //IN condition with array
							->orWhere(['emailAddresses.email'=>'test@intermesh.nl'])
						)
		);
	 * </code>
	 * 
	 * <p>Produces:</p>
	 * 
	 * <code>
	 * SELECT t.*, count(emailAddresses.id) FROM `contactsContact` t
	 * INNER JOIN `contactsContactEmailAddress` emailAddresses ON (`t`.`id` = `emailAddresses`.`contactId`)
	 * WHERE
	 * (
	 * 	`t`.`lastName` IS NOT NULL
	 * )
	 * AND
	 * (
	 * 	(
	 * 		`t`.`firstName` IN ("Merijn", "Wesley")
	 * 	)
	 * 	OR
	 * 	(
	 * 		`emailAddresses`.`email` = "test@intermesh.nl"
	 * 	)
	 * )
	 * AND
	 * (
	 * 	`t`.`deleted` != "1"
	 * )
	 * GROUP BY `t`.`id`
	 * HAVING
	 * (
	 *		count(emailAddresses.id) > 0
	 * )
	 * </code>
	 *
	 * @param Query|array|StringUtil $query Query object. When you pass an array a new 
	 * Query object will be autocreated and the array will be passed to 
	 * {@see Query::where()}.
	 * 
	 * @return static This is actually a {@see Store} object but to enable 
	 * autocomplete on the result we've set this to static.
	 * 
	 * You can also convert a store to a string to see the sql query;
	 */
	public static function find($query = null) {
		
		$query = Query::normalize($query);
		
		
		$calledClassName = get_called_class();
		
		$permissions = static::internalGetPermissions();
		$permissions->setRecordClassName($calledClassName);
		$permissions->applyToQuery($query);
		
		static::fireStaticEvent(self::EVENT_FIND, $calledClassName, $query);
		
		$store = new Store($calledClassName, $query);

		return $store;
	}

	/**
	 * Validates all attributes of this model
	 *
	 * You do not need to call this function. It's automatically called in the
	 * save function. Validators can be defined in defineValidationRules().
	 * 
	 * Don't override this function. Override {@see internalValidate()} instead.
	 * 
	 * @see defineValidationRules()
	 * @return boolean
	 */
	public final function validate() {
		
		if($this->fireEvent(self::EVENT_BEFORE_VALIDATE, $this) === false){			
			return false;
		}
		
		$success = $this->internalValidate();
		
		if(!$success) {
			\IFW::app()->debug(static::class.'::internalValidate returned '.var_export($success, true));
		}
		
		return $success;
	}	
	
	protected function internalValidate() {
		
		
		if ($this->isNew()) {
			//validate all columns
			$fieldsToCheck = $this->getColumns()->getColumnNames();
		} else {
			//validate modified columns
			$fieldsToCheck = array_keys($this->getModifiedAttributes());
		}
		
		$uniqueKeysToCheck = [];

		foreach ($fieldsToCheck as $colName) {
			if($colName == 'sortOrder') {
				continue;
			}
			
			$column = $this->getColumn($colName);
			if(!$this->validateRequired($column)){
				//only one error per column
				continue;
			}
			
			if (!empty($column->length) && !empty($this->$colName) && StringUtil::length($this->$colName) > $column->length) {
				$this->setValidationError($colName, 'maxLength', ['length' => $column->length, 'value'=>$this->$colName]);
			}
			
			if($column->unique && isset($this->$colName)){
				//set imploded key so no duplicates will be checked
				$uniqueKeysToCheck[implode(':', $column->unique)] = $column->unique;
			}
		}
		
		$validators = [];
		
		foreach(self::getValidationRules() as $validator){
			if(in_array($validator->getId(), $fieldsToCheck)){
				$validators[]=$validator;
			}			 
		}		
		
		
		//Disabled because it's better for performance to let mysql handle this.
//		foreach($uniqueKeysToCheck as $uniqueKeyToCheck){
//			$validator = new ValidateUnique($uniqueKeyToCheck[0]);
//			$validator->setRelatedColumns($uniqueKeyToCheck);
//			$validators[] = $validator;
//		}
		
		foreach ($validators as $validator) {
			if (!$validator->validate($this)) {

				$this->setValidationError(
								$validator->getId(), 
								$validator->getErrorCode(), 
								$validator->getErrorInfo()
								);
			}
		}
		
		if($this->fireEvent(self::EVENT_AFTER_VALIDATE, $this) === false){			
			return false;
		}
		
		return !$this->hasValidationErrors();
	}
	
	
	/**
	 * Find all keys that will be set by a relational save.
	 * 
	 * For example when saving a Car that has a required Dashboard. dashboardId 
	 * will be set to the id of the Dashboard after the relational save.	 
	 * 
	 * $car = new Car();	 
	 * $car->dashboard = new Dashboard();
	 * 
	 * $car->dashboardId is null but will be set to the ID of the Dashboard after 
	 * save. This has to be taken into account when saving.
	 */
	
	private function findKeysToBeSetByARelation() {
		
		$keysToBeSet = [];
		//loop through already set relations. $this->relations hold those
		foreach($this->relations as $relationName => $relationStore) {
			
			$relation = $this->getRelation($relationName);
			
			if($relation->hasMany()) {
				continue;
			}
			
			$record = $relationStore[0];
			
			if(!($record instanceof self)) {				
				continue;
			}
			
			$keys = $relation->getKeys();
						
			$toRecordName = $relation->getToRecordName();
			$toPks = $toRecordName::getPrimaryKey();		
						
			foreach($keys as $fromField => $toField) {
				//from field will be set by primary key of relation
				if(in_array($toField, $toPks)) {
					$keysToBeSet[] = $fromField;
				}
			}
		}
		
		return $keysToBeSet;
	}
	
	
	private function validateRequired(Column $column) {
		
		$ignore = $this->findKeysToBeSetByARelation();
		
		if ($column->required && !in_array($column->name, $ignore)) {

			switch ($column->pdoType) {
				case PDO::PARAM_BOOL:
				case PDO::PARAM_INT:
					if (!isset($this->{$column->name})) {
						$this->setValidationError($column->name, "required");
						return false;
					}
					break;
				default:
					if (empty($this->{$column->name})) {
						$this->setValidationError($column->name, "required");
						return false;
					}
					break;
			}
		}
		
		return true;
	}
	
	private function deleteCheckRestrictions(){
		$r = $this->getRelations();

		foreach ($r as $name => $relation) {
			if($relation->deleteAction === Relation::DELETE_RESTRICT) {
				if ($relation->isA(Relation::TYPE_HAS_ONE) || $relation->isA(Relation::TYPE_BELONGS_TO)){
					$result = $this->$name;
				}else{
					$result = $this->$name->single();
				}

				if ($result) {
					throw new DeleteRestrict($this, $relation);
				}
			}
		}
	}
	
	private function deleteCascade(){
		$r = $this->getRelations();

		foreach ($r as $name => $relation) {
			if($relation->deleteAction === Relation::DELETE_CASCADE) {
				$result = $this->$name;

				if ($result instanceof Store) {
					//has_many relations result in a statement.
					foreach ($result as $child) {
						if (!$child->equals($this)) {
							if(!$child->delete()){
								return false;
							}
						}
					}
				} elseif ($result) {
					//single relations return a model.
					if(!$result->delete()) {
						return false;
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Delete's the model from the database
	 * 
	 * You should rarely use this function. For example when cleaning up soft 
	 * deleted models.
	 *
	 * <p>Example:</p>
	 * <code>
	 * $model = User::findByPk(2);
	 * $model->deleteHard();
	 * </code>
	 *
	 * @return boolean
	 */
	public final function deleteHard() {		
		
		if(!$this->getColumn('deleted')) {
			throw new \Exception($this->getClassName()." does not support soft delete. Use delete() instead of hardDelete()");
		}
		
		return $this->processDelete(true);
	}
	
	/**
	 * Delete's the model from the database or set's it to deleted if soft delete 
	 * is supported.
	 *
	 * <p>Example:</p>
	 * <code>
	 * $model = User::findByPk(2);
	 * $model->delete();
	 * </code>
	 * 
	 * Don't override this method. Override {@see internalDelete()} instead. The 
	 * internalDelete function is called after permission checks and validation.
	 * 
	 * No database transactions are used because you should use MySQL cascading 
	 * deletes if you want to remove hard relations. For example a contact is 
	 * removed with it's email addresses with a MySQL cascade delete key.
	 * 
	 * Deletion of soft relations should probably be done without a database 
	 * transaction. For example when deleting a folder with files it is OK to 
	 * partially complete and removing the files on disk could cause problems when
	 * you rollback a transaction because the files on disk have been removed in
	 * the internalDelete() override of the file model.
	 *
	 * @return boolean
	 */
	public final function delete() {		
		return $this->processDelete();
	}
	
	
	private function processDelete($hard = false) {
		
		if(!$this->getPermissions()->can(PermissionsModel::PERMISSION_DELETE)) {
			throw new Forbidden("You're not permitted to delete ".$this->getClassName()." ".var_export($this->pk(), true));
		}

		if ($this->isNew) {
			IFW::app()->debug("Not deleting because this model is new");
			return true;
		}
		
		$this->deleteCheckRestrictions();		
		
		$success = $this->internalDelete($hard);
		if(!$success) {
			\IFW::app()->debug(static::class.'::internalDelete returned '.var_export($success, true));
		}
		if(!$this->fireEvent(self::EVENT_AFTER_DELETE, $this, $hard)) {			
			$success = false;
		}		
		
		return $success;
	}
	
	/**
	 * Get's the database connection
	 * 
	 * @return Connection
	 */
	protected function getDbConnection() {
		return IFW::app()->getDbConnection();
	}
	
	/**
	 * 
	 * Internal delete method
	 * 
	 * If you want to add functionality before or after delete then override this 
	 * method. This method is executed after 
	 * validation and permission checks so you don't have to worry about that.
	 * 
	 * @param boolean $hard true when the model will be deleted from the database even if it supports soft deletion.
	 * @return boolean
	 * @throws Exception
	 */
	protected function internalDelete($hard) {	
		
		if(!$this->fireEvent(self::EVENT_BEFORE_DELETE, $this, $hard)){			
			$this->getDbConnection()->rollBack();
			return false;
		}	
	
		if(!$this->deleteCascade()) {
			$this->getDbConnection()->rollBack();
			return false;
		}
		
		$soft = !$hard && $this->getColumn('deleted');
		$success = $soft ? $this->internalSoftDelete() : $this->internalHardDelete();

		if (!$success){
			
			$method = $soft ? 'internalSoftDelete' : 'internalHardDelete';
			\IFW::app()->debug(static::class.'::'.$method.' returned '.var_export($success, true));
			
			throw new Exception("Could not delete from database");
		}

		$this->isNew = !$soft;		
		$this->isDeleted = true;
		
		return true;
	}

	private function internalHardDelete() {
		$sql = "DELETE FROM `" . $this->tableName() . "` WHERE ";
	
		$first = true;
		foreach ($this->getPrimaryKey() as $field) {
			if (!$first){
				$sql .= ' AND ';
			}else{
				$first = false;
			}

			$column = $this->getColumn($field);

			$sql .= "`" . $field . '`=' . $this->getDbConnection()->getPDO()->quote($this->{$field}, $column->pdoType);
		}	

		try {
			return $this->getDbConnection()->query($sql);		
		} catch (Exception $e) {

			$msg = $e->getMessage();

			if (IFW::app()->getDebugger()->enabled) {
				$msg .= "\n\nFull SQL Query: " . $sql;

//				$msg .= "\n\n" . $e->getTraceAsString();

				IFW::app()->debug($msg);
			}
			throw $e;
		}
	}
	
	private function internalSoftDelete() {	
		
		if($this->isDeleted()){
			return true;
		}
		
		$this->deleted = true;
		
		if($this->update()){
			$this->markDeleted = false;
			return true;
		}else
		{
			return false;
		}
	}
	
	/**
	 * Tells if the record for this object is already deleted from the database.
	 * 
	 * @return boolean
	 */
	public function isDeleted(){
		return $this->isDeleted;
	}


	
	/**
	 * When the API returns this model to the client in JSON format it uses 
	 * this function to convert it into an array. 
	 * 
	 * 
	 * {@inheritdoc}
	 * 
	 * @param string $properties The properties that will be returned. By default 
	 * all properties will be returned except for relations 
	 * (See {@see getDefaultApiProperties()}. However modified relations will 
	 * always be returned.
	 * 
	 * @return array
	 */
	public function toArray($properties = null){	
	
		//If relations where modified then return them too
//		if(!empty($this->relations)) {
//			$defaultProperties = $this->getDefaultApiProperties();
//			$properties = new ReturnProperties($properties, $defaultProperties);	
			
//			foreach($this->relations as $relationName => $store) {				
//				Don't do this because it can result in infinite loops
//				if(!isset($properties[$relationName]) && $store->isModified()) {
//					
//					IFW::app()->debug("Adding extra return property '".$relationName."' in ".$this->getClassName()."'::toArray() because it was modified.");
//					
//					$properties[$relationName] = '';	
//				}
//			}
//		}
		
		
	
		$array = parent::toArray($properties);
		
		if(!isset($array['validationErrors']) && $this->hasValidationErrors()) {
			$array['validationErrors'] = $this->getValidationErrors();
		}
		
		//Always add primary key
		foreach($this->getColumns() as $column) {
			if($column->primary && !isset($array[$column->name])) {
				$array[$column->name] = $this->{$column->name};
			}
		}
		
		//Add className		
//		$array['className'] = self::getClassName();
		
		//Add validation errors even if not requested
		if($this->hasValidationErrors() && !isset($array['validationErrors'])) {
			$array['validationErrors'] = $this->getValidationErrors();
		}		
		
		$this->fireEvent(self::EVENT_TO_ARRAY, $this, $array);
		
		return $array;
	}	
	
//	
//	/**
//	 * Populates the record with database values. Used when relations are fetched 
//	 * with {@see Query::joinRelation()}
//	 * 
//	 * @param array $properties
//	 */
//	private function populate(array $properties) {
//		$this->loadingFromDatabase = true;
//		$this->isNew = false;
//		$this->setValues($properties);
//		$this->castDatabaseAttributes();
//		$this->loadingFromDatabase = false;
//	}

	
	

	/**
	 * Create a hasMany relation. 
	 * 
	 * For example a contact has many email addresses.
	 * 
	 * <code>
	 * public static function defineRelations() {
	 *	...
	 * 
	 *	self::hasMany('emailAddresses', ContactEmailAddress::class, ["id" => "contactId"]);
	 * 
	 *	...
	 * }
	 * </code>
	 *
	 * @param string $relatedModelName The class name of the related model. eg. UserGroup::class
	 * @param string $keys The relation keys. eg ['id'=>'userId']
	 * @return Relation
	 */
	public static function hasMany($name, $relatedModelName, array $keys){
		$calledClass = static::class;
		
		if(!isset(self::$relationDefs[$calledClass])) {
			self::$relationDefs[$calledClass] = [];
		}

		return self::$relationDefs[$calledClass][$name] = new Relation($name, $calledClass,$relatedModelName, $keys, true);
	}
	
	/**
	 * Create a has one relation. 
	 * 
	 * For example a user has one contact
	 * 
	 * <code>
	 * public static function defineRelations() {
	 *	...
	 * 
	 *	self::hasOne('userGroup', Contact::class, ["id" => "userId"]);
	 * 
	 *	...
	 * }
	 * </code>
	 *
	 * @param string $name The name of the relation
	 * @param string $relatedModelName The class name of the related model. eg. UserGroup::class
	 * @param string $keys The relation keys. eg ['id'=>'userId']
	 * @return Relation
	 */
	public static function hasOne($name, $relatedModelName, array $keys){
		$calledClass = static::class;
		
		if(!isset(self::$relationDefs[$calledClass])) {
			self::$relationDefs[$calledClass] = [];
		}

		return self::$relationDefs[$calledClass][$name] = new Relation($name, $calledClass,$relatedModelName, $keys, false);
	}
	
//	
//	/**
//	 * Copy this model
//	 * 
//	 * It only copies the database attributes and relations that are 
//	 * {@see Relation::isIdentifying()} and not {@see Relation::isBelongsTo()}.
//	 * 
//	 * <code>
//	 * $model = $model->copy();	
//	 * </code>
//	 * 
//	 * 
//	 * @param array $attributes
//	 * @return \self
//	 */
//	public function copy($properties) {
//		$copy = new static;
//		
//		//parent doesn't add PK's
////		$array = parent::toArray($properties);
//		
//		foreach($this->getColumns() as $column) {
//			if(!$column->primary || !$column->autoIncrement) {
//				$copy->{$column->name} = $this->{$column->name};
//			}
//		}
//		
//		foreach($this->getRelations() as $relation) {
//			if($relation->isIdentifying() && !$relation->isBelongsTo()) {
//				if($relation->hasMany()) {
//					foreach($this->{$relation->getName()} as $relatedModel) {
//						$copy->{$relation->getName()}[] = $relatedModel->copy();
//					}
//				}else
//				{
//					$relatedModel = $this->{$relation->getName()};
//					if($relatedModel) {
//						$copy->{$relation->getName()} = $relatedModel->copy();
//					}
//				}
//			}
//		}
//		return $copy;
//	}

	/**
	 * Truncates the modified database attributes to the maximum length of the 
	 * database column. Can be useful when importing stuff.
	 */
	public function truncateModifiedAttributes() {
		foreach($this->getModifiedAttributes() as $attributeName => $oldValue) {
			$this->$attributeName = mb_substr($this->$attributeName, 0, $this->getColumn($attributeName)->length);
		}
	}
	
	/**
	 * Creates the permissions model
	 * 
	 * By default only admins can access. Override this method to give it other
	 * permissions.
	 * 
	 * @return AdminsOnly
	 */
	protected static function internalGetPermissions() {
		return new AdminsOnly();
	}	
	
	
	/**
	 * Get the permissions model
	 * 
	 * See {@see PermissionsModel} for more information about how to implement 
	 * record permissionss
	 * 
	 * Override {@see internalGetPermissions()} to implement another permissons model.
	 * 
	 * @return PermissionsModel
	 */
	public final function getPermissions() {		
		if(!isset($this->permissions)) {
			$this->permissions = $this->internalGetPermissions();
		}
		
		$this->permissions->setRecord($this);
		
		return $this->permissions;
	}
}
