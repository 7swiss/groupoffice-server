<?php
namespace GO\Core\Auth\Permissions\Model;

use GO\Core\Users\Model\Group;
use GO\Core\Users\Model\UserGroup;
use IFW\Orm\Record;

/**
 * The GroupAccess record
 *
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class GroupAccess extends Record {

	/**
	 * 
	 * @var int
	 */
	public $groupId;
	
	/**
	 * Define the relation that these groups grant access for.
	 * 
	 * @example
	 * ```````````````````````````````````````````````````````````````````````````
	 * return self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @return \IFW\Orm\Relation
	 */
	abstract protected static function groupsFor();


	protected static function defineRelations() {
		
		

		static::hasMany('groupUsers', UserGroup::class, ['groupId' => 'groupId']);
		static::hasOne('group', Group::class, ['groupId' => 'id']);		
		
		static::groupsFor();		

		parent::defineRelations();
	}	
	
	/**
	 * This record must have a double primary key. One field is groupId. This function returns the other. For example "contactId".
	 */
	public static function getForPk() {
		$pk = self::getPrimaryKey();
		foreach($pk as $field) {
			if($field != 'groupId') {
				return $field;
			}
		}
		
		throw new \Exception("The GroupAccess record ".self::getClassName().' must implement a double primary key. One of the fields must be "groupId".');
	}

	protected static function internalGetPermissions() {
		
		static::defineRelations(); //to avoid cache problems
		$relation = static::groupsFor();
		
		return new \GO\Core\Auth\Permissions\Model\GroupAccessPermissions($relation->getName());
	}

}
