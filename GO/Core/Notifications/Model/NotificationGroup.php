<?php
namespace GO\Core\Notifications\Model;

/**
 * The NotificationGroup model
 *
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class NotificationGroup extends \GO\Core\Orm\Record {
	/**
	 * 
	 * @var int
	 */							
	public $notificationId;

	/**
	 * 
	 * @var int
	 */							
	public $groupId;

	protected static function defineRelations() {
		self::hasOne('group', \GO\Core\Users\Model\Group::class, ['groupId' => 'id']);
		self::hasOne('groupUsers', \GO\Core\Users\Model\UserGroup::class, ['groupId' => 'groupId']);
		self::hasOne('notification', Notification::class, ['notificationId' => 'id']);
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('notification');
	}
	
}
