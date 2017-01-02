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

class NotificationAppearance extends \GO\Core\Orm\Record {
	/**
	 * 
	 * @var int
	 */							
	public $notificationId;

	/**
	 * 
	 * @var int
	 */							
	public $userId;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $dismissedAt;

	protected static function defineRelations() {
		self::hasOne('user', \GO\Core\Users\Model\User::class, ['userId' => 'id']);
		self::hasOne('notification', Notification::class, ['notificationId' => 'id']);
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('notification');
	}
	
}
