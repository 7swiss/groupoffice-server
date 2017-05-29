<?php

namespace GO\Core\Notifications\Model;

use DateTime;
use GO\Core\Install\Model\System;
use GO\Core\Orm\Model\RecordType;
use GO\Core\Orm\Record;
use GO\Core\Users\Model\User;
use IFW\Auth\Permissions\Everyone;
use IFW\Orm\Query;
use PDO;
use function GO;

/**
 * The Notification model
 * 
 * Notifications are always linked to a record. The garbage collector should
 * cleanup expired notifications.
 * 
 * When it's created the {@see Watch} records are used to create 
 * {@see NotificationGroup} entries.
 * 
 * @example create watch
 * ````````````````````````````````````````````````````````````````````````````
 * Watch::create($this, $this->assignee->group->id);
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * @example inside a {@see Record}
 * ````````````````````````````````````````````````````````````````````````````
 * 
 * protected function internalSave() {
 * 	$notification = new \GO\Core\Notifications\Model\Notification();
 * 	$notification->iconBlobId = $this->photoBlobId;
 * 	$notification->type = $logAction;		
 * 	$notification->data = $this->toArray('id,name');
 * 	$notification->record = $this;		
 * 	return $notification->save();
 * }
 * 
 * ````````````````````````````````````````````````````````````````````````````
 *
 * 
 * @property NotificationGroup[] $for
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Notification extends Record implements \GO\Core\GarbageCollection\GarbageCollectionInterface {

	/**
	 * 
	 * @var int
	 */
	public $id;

	/**
	 * 
	 * @var int
	 */
	public $createdBy;

	/**
	 * 
	 * @var DateTime
	 */
	public $createdAt;

	/**
	 * 
	 * @var DateTime
	 */
	public $triggerAt;

	/**
	 * 
	 * @var string
	 */
	public $iconBlobId;

	/**
	 * 
	 * @var DateTime
	 */
	public $expiresAt;

	/**
	 * 
	 * @var string
	 */
	public $type;

	/**
	 * 
	 * @var string
	 */
	public $category = 'message';

	/**
	 * 0=min,1=low,2=default,3=high,4=max
	 * @var int
	 */
	public $priority = 2;

	/**
	 * 
	 * @var int
	 */
	public $recordTypeId;

	/**
	 * 
	 * @var int
	 */
	public $recordId;

	/**
	 * 
	 * @var string
	 */
	protected $data;

	const CATEGORY_MESSAGE = 'message';
	const CATEGORY_PROGRESS = 'progress';

	const LOG_ACTION_ERROR = 'error';
	
	private static $suspended = false;

	protected static function defineRelations() {
		self::hasOne('creator', User::class, ['createdBy' => 'id']);
		self::hasOne('about', RecordType::class, ['recordTypeId' => 'id']);
		self::hasMany('for', NotificationGroup::class, ['id' => 'notificationId']);
		self::hasMany('appearances', NotificationAppearance::class, ['id' => 'notificationId']);
	}

	protected static function internalGetPermissions() {

		//todo only for
		return new Everyone();
	}
	
	/**
	 * Avoid creating notifications
	 */
	public static function suspend() {
		self::$suspended = true;
	}
	
	/**
	 * Resume create notifications
	 */
	public static function resume() {
		self::$suspended = false;	
	}

	/**
	 * Create a notification
	 * 
	 * @example inside Record::internalSave()
	 * ```
	 * protected function internalSave()
	 * 	if(!Notification::create(\IFW\Orm\Record::LOG_ACTION_CREATE, $this->toArray('id,name'), $this, $this->photoBlobId)) {
				return false;
			}
	 * 
	 *	return parent::internalSave()'
	 * }
	 * 
	 * ```
	 * 
	 * @param string $type eg. \IFW\Orm\Record::LOG_ACTION_UPDATE
	 * @param array $data Arbitrary data to include
	 * @param Record $record The record this notification belongs to.
	 * @param int $iconBlobId An icon
	 * @param int[] $forGroupId  Groups that should see this notification
	 * 
	 * @return self
	 */
	public static function create($type, $data, Record $record, $iconBlobId = null, $forGroupId = null) {
		
		if(self::$suspended) {
			return true;
		}

		$notification = new self();
		$notification->iconBlobId = isset($iconBlobId) ? $iconBlobId : GO()->getAuth()->user()->photoBlobId;
		$notification->type = $type;
		$notification->setData($data);
		$notification->record = $record;

		if(!isset($forGroupId)) {
			$watches = Watch::find(['recordTypeId' => $notification->recordTypeId, 'recordId' => $notification->recordId]);
			if (!$watches->getRowCount()) {
				return true;
			}
			foreach ($watches as $watch) {
				$notification->for[] = ['groupId' => $watch->groupId];
			}
		}else
		{
			foreach($forGroupId as $groupId) {
				$notification->for[] = ['groupId' => $groupId];
			}
		}
		if (!$notification->save()) {
			return false;
		} else {
			return true;
		}
	}
	
	public static function findByRecord(Record $record, $type) {
		$recordId = implode('-', $record->pk());
		$recordTypeId = $record->getRecordType()->id;
		
		return self::find(['recordId' => $recordId, 'recordTypeId' => $recordTypeId, 'type' => $type]);
	}



	/**
	 * Links this notification to a record
	 * 
	 * @param Record $record
	 */
	public function setRecord(Record $record) {
		$this->recordId = implode('-', $record->pk());
		$this->recordTypeId = $record->getRecordType()->id;
	}

	public function setData($data) {
		$this->data = json_encode($data);
	}

	public function getData() {
		return json_decode($this->data, true);
	}

	protected function init() {
		parent::init();

		$this->triggerAt = new DateTime();
		$this->expiresAt = new DateTime('+1 month');
	}

	/**
	 * Dismiss a notification for a user
	 *
	 * @param int $userId
	 * @return boolean
	 */
	public function dismiss($userId) {
		$a = NotificationAppearance::findByPk(['notificationId' => $this->id, 'userId' => $userId]);
		if (!$a) {
			$a = new NotificationAppearance();
			$a->userId = $userId;
			$a->notificationId = $this->id;
		}
		$a->dismissedAt = new DateTime();
		return $a->save();
	}

	public static function countForCurrentUser() {
		if (!System::isDatabaseInstalled() || !GO()->getAuth()->user()) {
			return null;
		}

		$currentUserId = GO()->getAuth()->user()->id();

		$query = (new Query())
						->select('count(DISTINCT t.id)')
						->fetchMode(PDO::FETCH_COLUMN, 0)
						->joinRelation('for.groupUsers')
						->where(['for.groupUsers.userId' => $currentUserId])
						->joinRelation('appearances', false, 'LEFT', ['appearances.userId' => $currentUserId])
						->andWhere(['>', ['expiresAt' => new DateTime()]])
						->andWhere(['appearances.userId' => null]);

		return (int) Notification::find($query)->single();
	}

	public static function collectGarbage() {		
		//delete expired notifications
		GO()->getDbConnection()->createCommand()->delete(self::tableName(), ['<=', ['expiresAt' => new DateTime()]])->execute();
	}

}
