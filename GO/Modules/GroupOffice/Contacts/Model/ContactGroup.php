<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use GO\Core\Users\Model\Group;
use GO\Core\Users\Model\UserGroup;
use IFW\Orm\Record;

/**
 * The ContactGroup model
 *
 * @property boolean $read 
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class ContactGroup extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $contactId;

	/**
	 * 
	 * @var int
	 */							
	public $groupId;

	/**
	 * 
	 * @var bool
	 */							
	public $write = true;

	/**
	 * 
	 * @var bool
	 */							
	public $delete = true;

	protected static function defineRelations() {
		
		self::hasMany('groupUsers', UserGroup::class, ['groupId'=>'groupId']);
		self::hasOne('group', Group::class, ['groupId'=>'id']);
		self::hasOne('contact', Contact::class, ['contactId'=>'id']);
		
		parent::defineRelations();
	}
	
	protected static function internalGetPermissions() {
		return new ContactGroupPermissions();
	}
	
}
