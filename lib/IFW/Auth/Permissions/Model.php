<?php

namespace IFW\Auth\Permissions;

use IFW;
use IFW\Data\Model as DataModel;
use IFW\Orm\Query;
use IFW\Orm\Record;

/**
 * Abstract permissions model to secure records
 * 
 * Because the API can save relationally, every model must secure itself.
 * Permissions can't be checked in the controller because the API can save 
 * related models. For example a project can be modified through a task:
 * 
 * {
 * 	description: "A task",
 *  project: {
 * 		name: "The project of the task"
 * 	}
 * }
 * 
 * This is a very powerful feature but can also be dangerous if the models
 * don't check permissions.
 * 
 * The following actions are checked in the record functions:
 * 
 * read: {@see Record::__construct()}
 * create: {@see Record::save()} and {@see Record::__construct()}
 * update: {@see Record::save()} and {@see Record::delete()}
 * manage: {@see Record::validate()}
 * 
 * 
 * **Note** that find queries are not aware of permissions. You must implement the
 * right query yourself in {@see internalQuery}
 * 
 * Relations
 * ---------
 * 
 * There's one exception for permissions. When a model is fetched by relation.
 * For exampe $contact->organizations. They will be readable even without read
 * permisions. This is by design so we can still show those organizations in 
 * the application but you can't navigate to them. You can't access relations
 * of the organizations in this example. In other words not readable relations 
 * can be fetched but not the relations of that record.
 * 
 */
abstract class Model extends DataModel {

	/**
	 * Checked in toArray of {@see AbstractRecord}
	 */
	const PERMISSION_READ = "read";

	/**
	 * Checked in save() function of {@see AbstractRecord}
	 */
	const PERMISSION_UPDATE = "update";

	/**
	 * Checked in save() function of {@see AbstractRecord}
	 */
	const PERMISSION_CREATE = "create";

	/**
	 * Checked in validate() function of {@see AbstractRecord}
	 */
	const PERMISSION_MANAGE = 'manage';
	
	/**
	 * Disable all permission checks
	 * 
	 * Disabled during installation and {@see \IFW\App::init()}
	 * 
	 * @var boolean
	 */
	public static $enablePermissions = false;

	/**
	 * The record that these permissions are for.
	 
	 * 
	 * @var Record
	 */
	protected $record;
	private $cache = [];
	
	/**
	 * The record class name that this permissions object is instantiated from
	 * 
	 * @var string $recordClassName
	 */
	protected $recordClassName;
	
	/**
	 * @param DataModel $record
	 */
	public function setRecord(DataModel $record) {
		$this->record = $record;
		$this->cache = [];
		
		$this->setRecordClassName($record->getClassName());
	}
	/**
	 * The record class name that this permissions object is instantiated from
	 * 
	 * @param string $recordClassName
	 */
	public function setRecordClassName($recordClassName) {
		$this->recordClassName = $recordClassName;
	}
	
	
	/**
	 * Return permission types.
	 * 
	 * It can be a string for a writable permission ttype or an array with name and readonly as key for readonly properties
	 * 
	 * @return string[]|array[] eg [self::PERMISSION_READ, ['name' => self::PERMISSION_SPECIAL, 'readonly' => true]
	 */
	protected function definePermissionTypes() {
		return [
				self::PERMISSION_READ,
				self::PERMISSION_UPDATE,
				self::PERMISSION_CREATE,
				self::PERMISSION_MANAGE,
		];
	}
	
	/**
	 * Get all the permission types
	 * 
	 * @return array[] [['name' => 'read', 'readonly'=>false], ['name' => 'update', 'readonly'=>false]]
	 */
	public function getPermissionTypes() {
		return self::normalizePermissionTypes($this->definePermissionTypes());
	}
	
	private static function normalizePermissionTypes(array $types) {
		for($i=0,$c=count($types);$i<$c;$i++) {
			if(!is_array($types[$i])) {
				$types[$i] = ['name' => $types[$i], 'readonly' => false];
			}
		}
		
		return $types;
	}

	public function toArray($properties = null) {
		
		$return = [];
		
		foreach ($this->getPermissionTypes() as $type) {
				$return[$type['name']] = $this->can($type['name']);
		}
		
		return $return;
	}

	

	/**
	 * This function does the actual check if the logged in user is authorized to
	 * do this action.
	 * 
	 * Admins can always do any action you don't need to check this in your 
	 * permission models.
	 * 
	 * @param string $permissionType
	 * @param \IFW\Auth\UserInterface $user
	 * 
	 * @return boolean
	 */
	abstract protected function internalCan($permissionType, IFW\Auth\UserInterface $user);
		
	
	private static $isCheckingPermissions = false;

	/**
	 * Returns true while a permission check is made.
	 * 
	 * It's used in IFW\Orm\Record::__construct() to avoid infinite loops.
	 * 
	 * @return boolean
	 */
	public static function isCheckingPermissions() {
		return self:: $isCheckingPermissions;
	}
	
	/**
	 * Checks if a the current user can do an action.
	 * 
	 * Admins can always do any action.
	 * 
	 * @param string $permissionType
	 * @return boolean
	 */
	public final function can($permissionType, IFW\Auth\UserInterface $user = null) {
		
		if(!self::$enablePermissions) {
				return true;
		}
		
		if($user == null) {
			$user = IFW::app()->getAuth()->user();
			if(!$user) {
				return false;
			}
		}

		$oldIsChecking = self::$isCheckingPermissions;
		self::$isCheckingPermissions = true;
		
		try {
			
			if(!isset($this->cache[$permissionType.'-'.$user->id()])) {
				if($user->isAdmin()){
					$this->cache[$permissionType.'-'.$user->id()] = true;
				}else
				{					
					if(in_array($permissionType, $this->record->allowedPermissionTypes()) || in_array('*', $this->record->allowedPermissionTypes())) {
						$can = true;
					}else
					{					
						$can = $this->internalCan($permissionType, $user);
					}
					
//					if(!$can) {
//						IFW::app()->debug("User ".$user->id." has no permission for ".$this->record->getClassName().' permissionType:'.var_export($permissionType, true).' '.var_export($this->record->pk(), true));
//					}
					
					$this->cache[$permissionType.'-'.$user->id()] = $can;
				}
			}
		} finally {
//			IFW::app()->debug($this->record->getClassName().'::finally can('.$permissionType.': '.var_export($oldIsChecking, true).')');
			self::$isCheckingPermissions = $oldIsChecking;
		}		
		

		return $this->cache[$permissionType.'-'.$user->id()];
	}
	
	public final function applyToQuery($query = null) {

		if(!self::$enablePermissions || self::$isCheckingPermissions) {			
//			GO()->debug("SKIPPED: ".$this->recordClassName.' '.var_export(self::$isCheckingPermissions, true).' '.var_export(self::$enablePermissions, true));
			return;
		}
			
			
		self::$enablePermissions = false;

		$user = \IFW::app()->getAuth()->user();
		
		if(!$user) {
			throw new IFW\Exception\NotAuthenticated();
		}
				
		if($user && $user->isAdmin()) {		
			self::$enablePermissions = true;
			return;
		}

		//Group all existing where criteria. For example WHERE id=1 OR id=2 will become WHERE (id=1 OR id=2)
		$criteria = $query->getWhereAsCriteria();
		$query->resetCriteria();
		
		$this->internalApplyToQuery($query, $user);
		
		if (isset($criteria)) {
			$query->andWhere($criteria);
		}

		self::$enablePermissions = true;

	}
	
	protected function internalApplyToQuery(Query $query, \IFW\Auth\UserInterface $user){
		
	}
	
	/**
	 * Override this function to initialize your permissions on a new record
	 * 
	 * @param Record $record
	 */
	public function beforeCreate(Record $record) {
		if($record->getRelation('groups') && property_exists($record, 'ownedBy') && !$record->isModified('groups')) {
			$record->groups[] = ['groupId' => $record->ownedBy, 'write'=>true];
		}
	}
}
