<?php
namespace GO\Core\Notifications\Model;

use DateTime;
use GO\Core\Blob\Model\BlobNotifierTrait;
use GO\Core\Orm\Record;
use IFW\Auth\Permissions\Everyone;
use IFW\Orm\Query;
use PDO;

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
 *	$notification = new \GO\Core\Notifications\Model\Notification();
 *	$notification->iconBlobId = $this->photoBlobId;
 *	$notification->type = $logAction;		
 *	$notification->data = $this->toArray('id,name');
 *	$notification->record = $this;		
 *	return $notification->save();
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
class Notification extends Record {
	
	
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
	 * @var \DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $triggerAt;

	/**
	 * 
	 * @var string
	 */							
	public $iconBlobId;

	/**
	 * 
	 * @var \DateTime
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
	
	use BlobNotifierTrait;

	protected static function defineRelations() {
		self::hasOne('creator', \GO\Core\Users\Model\User::class, ['createdBy'=>'id']);
		self::hasOne('about', \GO\Core\Orm\Model\RecordType::class, ['recordTypeId'=>'id']);
		self::hasMany('for', NotificationGroup::class, ['id'=>'notificationId']);
		self::hasMany('appearances', NotificationAppearance::class, ['id'=>'notificationId']);
	}
	
	protected static function internalGetPermissions() {
		
		//todo only for
		return new Everyone();
	}
	
	protected function internalSave() {
		
		$this->saveBlob('iconBlobId');
		
		if($this->isNew()) {
			$watches = Watch::find(['recordTypeId'=>$this->recordTypeId, 'recordId' => $this->recordId]);

			foreach($watches as $watch) {
				$this->for[] = ['groupId'=>$watch->groupId];
			}
		}
		
		return parent::internalSave();
	}
	
	protected function internalDelete($hard) {
		
		$this->freeBlob($this->iconBlobId);		
		
		return parent::internalDelete($hard);
	}
	
	/**
	 * Links this notification to a record
	 * 
	 * @param Record $record
	 */
	public function setRecord(Record $record){
		$this->recordId = implode('-',$record->pk());
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
		
		$this->triggerAt = new \DateTime();
		$this->expiresAt = new \DateTime('+1 month');
	}
	
	/**
	 * Dismiss a notification for a user
	 *
	 * @param int $userId
	 * @return boolean
	 */
	public function dismiss($userId) {
		$a = NotificationAppearance::findByPk(['notificationId'=>$this->id, 'userId'=>$userId]);
		if(!$a) {
			$a = new NotificationAppearance();
			$a->userId = $userId;
			$a->notificationId = $this->id;
		}
		$a->dismissedAt = new \DateTime();
		return $a->save();
	}
	
	
	public static function countForCurrentUser() {
		if(!GO()->getAuth()->user()){
			return null;
		}

		$currentUserId = GO()->getAuth()->user()->id();
		
		$query = (new Query())
						->select('count(DISTINCT t.id)')
						->fetchMode(PDO::FETCH_COLUMN, 0)
						->where(['for.groupUsers.userId' => $currentUserId])
						->joinRelation('appearances', false, 'LEFT', ['appearances.userId' => $currentUserId])						
						->andWhere(['>',['expiresAt' => new \DateTime()]])
						->andWhere(['appearances.userId'=>null]);

		return (int) Notification::find($query)->single();
		
	}
	
}