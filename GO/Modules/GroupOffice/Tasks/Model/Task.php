<?php
namespace GO\Modules\GroupOffice\Tasks\Model;

use DateTime;
use GO\Core\Notifications\Model\Notification;
use GO\Core\Notifications\Model\Watch;
use GO\Core\Orm\Record;
use GO\Core\Tags\Model\Tag;
use GO\Core\Users\Model\User;
use IFW\Orm\Query;

/**
 * The task model
 *
 
 *  
 * @property User $creator
 * @property \GO\Core\Users\Model\Group $assignee
 * 
 * @property TaskReminders[] $reminders
 * @property TaskTags[] $tags
 * 
 * @property GO\Modules\GroupOffice\Contacts\Model\Contact $contact
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Wesley Smits <wsmits@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Task extends Record {


	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var string
	 */							
	public $description;

	/**
	 * 
	 * @var string
	 */							
	public $notes;

	/**
	 * 
	 * @var DateTime
	 */							
	public $dueAt;

	/**
	 * 
	 * @var int
	 */							
	public $duration;

	/**
	 * 
	 * @var DateTime
	 */							
	public $completedAt;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * 
	 * @var DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var DateTime
	 */							
	public $modifiedAt;

	/**
	 * 
	 * @var int
	 */							
	public $createdBy;

	/**
	 * 
	 * @var int
	 */							
	public $assignedTo;
	
	/**
	 * 
	 * @var int
	 */							
	public $accountId;

	const STATUS_FINISHED = 1;

	const STATUS_UNFINISHED = 0;
	
	const NOTIFY_TYPE_COMPLETED = 'completed';
	
	
	public static function defineRelations(){
		self::hasOne('account', \GO\Core\Accounts\Model\Account::class, ['accountId' => 'id']);		
		self::hasOne('creator', User::class, ['createdBy'=>'id']);
		self::hasMany('tags',Tag::class, ['id'=>'taskId'], true)
						->via(TaskTag::class,['tagId'=>'id'])
						->setQuery((new Query())->orderBy(['name'=>'ASC'])); // TODO: order by label? (Rename name to blabel?)
		self::hasMany('reminders',Reminder::class, ['id'=>'taskId'], true)
						->via(Taskreminder::class,['tagId'=>'id'])
						->setQuery((new Query())->orderBy(['time'=>'ASC']));
		self::hasOne('assignee', \GO\Core\Users\Model\Group::class, ['assignedTo'=>'id']);
		
		self::hasMany('comments', \GO\Core\Comments\Model\Comment::class, ['id'=>'taskId'])->via(TaskComment::class, ['commentId'=>'id']);
		
//		self::hasMany('links', Link::class, ['id' => 'fromRecordId'])
//						->setQuery((new Query())->where(['links.fromRecordTypeId' => self::getRecordType()->id]));
	
//		self::hasOne('customfields', TaskCustomFields::class, ['id' => 'id']);		// TODO: add Customfield support
		
		\GO\Modules\GroupOffice\Contacts\Model\Contact::hasMany('tasks', Task::class, ['id'=>'contactId'])
							->via(ContactTask::class, ['taskId' => 'id']);


		self::hasOne('contact', \GO\Modules\GroupOffice\Contacts\Model\Contact::class, ['id'=>'taskId'])
						->via(ContactTask::class, ['contactId' => 'id']);

		parent::defineRelations();
	}
	
	protected function init() {
		parent::init();
		
		if($this->isNew()) {
			$this->assignee = GO()->getAuth()->user()->group;
			$this->account = \GO\Core\Accounts\Model\Account::findByCapability(self::class)->single();
		}
	}
	
	protected function internalSave() {

		
		if(!parent::internalSave()) {
			return false;
		}		
		
		$notifyType = $this->isNew() ? self::NOTIFY_TYPE_CREATE : self::NOTIFY_TYPE_UPDATE;
		
		if($this->isModified('completedAt') && isset($this->completedAt)) {
			$notifyType = self::NOTIFY_TYPE_COMPLETED;
		}
		
		if($this->isModified('assignedTo') || !empty($this->assignedTo)) {			
			Watch::create($this, $this->assignedTo);
		}	
		
		if(!Notification::create($notifyType, $this->toArray('id,description,dueAt'), $this)) {
			return false;
		}

		
		return true;
	}
	
	protected static function internalGetPermissions() {
		return new \GO\Core\Accounts\Model\AccountItemPermissions();
	}
}
