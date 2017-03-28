<?php
namespace GO\Modules\GroupOffice\Messages\Model;

use Exception;
use GO\Core\Accounts\Model\Account;
use GO\Core\Links\Model\Link;
use GO\Core\Orm\Record;
use GO\Core\Tags\Model\Tag;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use IFW\Auth\Permissions\ViaRelation;
use IFW\Fs\Folder;
use IFW\Orm\Query;
use PDO;


/**
 * The Thread model
 *
 * 
 *
 * 
 * @property Folder $folder
 * @property Message[] $messages
 * @property Tag[] $tags
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Thread extends Record {	
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $accountId;

	/**
	 * 
	 * @var string
	 */							
	public $subject;

	/**
	 * 
	 * @var bool
	 */							
	public $answered = false;

	/**
	 * 
	 * @var bool
	 */							
	public $forwarded = false;

	/**
	 * 
	 * @var bool
	 */							
	public $seen = false;

	/**
	 * 
	 * @var bool
	 */							
	public $flagged = false;

	/**
	 * If any of the thread messages has this flag, The thread will have this set.
	 * @var bool
	 */							
	public $hasAttachments = false;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $lastMessageSentAt;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $modifiedAt;
	
	/**
	 * Excerpt of the latest message in the thread
	 * @var string
	 */							
	protected $excerpt;

	/**
	 * 
	 * @var int
	 */							
	public $messageCount;

	/**
	 * 
	 * @var string
	 */							
	public $photoBlobId;

	protected static function defineRelations() {		
		self::hasOne('account', Account::class, ['accountId' => 'id']);
		
		self::hasMany('messages', Message::class, ['id' => 'threadId']);
		
		self::hasMany('links', Link::class, ['id' => 'fromRecordId'])
						->setQuery((new Query())->where(['links.fromRecordTypeId' => self::getRecordType()->id]));
		
		self::hasMany('tags',Tag::class, ['id'=>'threadId'], true)
					->via(ThreadTag::class,['tagId'=>'id'])
					->setQuery((new Query())->orderBy(['name'=>'ASC']));
		
		parent::defineRelations();
		
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation('account');
	}
	
	/**
	 * Get all addresses that have sent a message in this thread
	 * 
	 * @return Address[]
	 */
	public function getFrom(){

		$q = (new Query())
//				->distinct()
				->joinRelation('message.messages', false)
				->select('ANY_VALUE(t.personal) AS personal, t.address')
				->where(['message.messages.threadId' => $this->id])
				->groupBy(['address']);
		
		return Address::find($q);
	}
	
	
	public function setType($type) {
		foreach($this->messages as $message) {
			if($message->type != Message::TYPE_SENT) {
				$message->type = $type;
				if(!$message->save()){
					throw new Exception("Couldn\'t save message");
				}
			}
		}
		
		return true;
	}
	
	
	public function getExcerpt() {
		if(!$this->isNew() && !isset($this->excerpt)) {			
			$this->excerpt = $this->findLatestMessage()->getExcerpt();
			$this->update();
		}
		return $this->excerpt;
	}
	
	
	private $latestMessage;
	/**
	 * Get the latest message in the thread
	 * 
	 * @return Message
	 */
	public function findLatestMessage() {
		
		if(!isset($this->latestMessage)) {
			$query = (new Query())							
							->where(['threadId' => $this->id])
							->orderBy(['sentAt'=>'DESC']);

			$this->latestMessage = Message::find($query)->single();
		}
		
		return $this->latestMessage;
	}	
	
	/**
	 * Update all threads that are not in sync with their messages and deletes
	 * threads that no longer have messages.
	 */
	public static function syncAll($accountId) {
		
		$threads = Thread::find(
						(new Query())
						->distinct()
						->select('t.id')
						->joinRelation('messages')
						->where(['accountId'=>$accountId])
						->andWhere('t.messageCount IS NUll OR t.modifiedAt < messages.modifiedAt')						
						);		
		
		GO()->debug($threads->getRowCount().' threads out of sync');
		
		foreach($threads as $thread) {			
			if(!$thread->sync(false)) {
				throw new Exception("Could not save thread");
			}
		}
		
		self::deleteEmptyThreads($accountId);
	}
	
	public static function deleteEmptyThreads($accountId) {
		$threads = Thread::find(
						(new Query())
						->withDeleted()
						->select('t.id,t.accountId')
						->joinRelation('messages',false, 'LEFT')
						->where(['accountId'=>$accountId, 'messages.id' => null])
						);
						
		
		GO()->debug($threads->getRowCount().' threads to delete');
		
		foreach($threads as $thread) {			
			if(!$thread->delete()) {
				throw new Exception("Could not delete thread");
			}
		}
	}
	
	/**
	 * Set the thread properties by giving it the latest thread message
	 * 
	 * @param \GO\Modules\GroupOffice\Messages\Model\Message $latest
	 * @param bool $getExcerpt Get the body too. When doing a first index it's way faster not to get the bodies. We do that lazy.
	 */
	public function setLatestMessage(Message $latest, $getExcerpt = true) {
		$this->photoBlobId = $latest->photoBlobId;
		
		$this->subject = $latest->subject;			
		$this->answered = $latest->type == Message::TYPE_SENT || $latest->type == Message::TYPE_OUTBOX;
		
		//prevent double fetch of body with this if
		if($getExcerpt && (!isset($this->excerpt) || $latest->sentAt != $this->lastMessageSentAt)) {
			$this->excerpt = $latest->getExcerpt();			
		}
		$this->lastMessageSentAt = $latest->sentAt;
		$this->seen = $latest->seen;
		$this->answered = $latest->answered;
		
		//messageCount must stay null to trigger a resync after import!
		$this->messageCount = null;
		
		
	}
	
	public function sync($getExcerpt = true) {
		
		$latest = $this->findLatestMessage();		
		
		$this->setLatestMessage($latest, $getExcerpt);
		
		$q = (new Query())
				->select('count(*) as messageCount, min(seen) AS seen, max(flagged) AS flagged, min(inReplyToId) as minReplyToId')						
				->fetchMode(PDO::FETCH_ASSOC)
				->where(['threadId'=>$this->id]);

		$values = Message::find($q)->single();

		$this->seen = (bool) $values['seen'];		
		$this->flagged = (bool) $values['flagged'];
		$this->messageCount = (int) $values['messageCount'];
		$this->answered = $values['minReplyToId'] > 0;
		$this->hasAttachments = Attachment::find(
						(new Query)
							->andWhere(['message.messages.threadId' => $this->id, "contentId" => null])
							->joinRelation('message.messages')
						)->single() != false;

		return $this->update();		
	}
	
//	public function getExcerpt(){
//		if($this->excerpt == NULL && ($latest = $this->findLatestMessage())) {
//			$this->excerpt = $latest->getExcerpt();	
//			if(!$this->save()) {
//				throw new Exception("failed to set excerpt");
//			}
//		}
//		
//		return $this->excerpt;
//	}
	
	
	/**
	 * 
	 * @param Contact $contact
	 * @return self
	 */
	public static function findByContact(Contact $contact, Query $query = null) {
		
		$emails = [];
		foreach($contact->emailAddresses as $emailAddress) {
			$emails[] = $emailAddress->email;
		}
		
		if(!isset($query)) {
			$query = (new Query());
		}

		$query->joinRelation('messages.addresses', false)
						->andWhere(['addresses.email' => $emails])
						->groupBy(['t.id']);
		
		return Thread::find($query);
	}
	
	
	protected function internalSave() {
		if(!$this->isSavedByRelation() && $this->isModified(['flagged','seen'])) {
			if(($this->isModified('flagged') && $this->flagged) || ($this->isModified('seen') && !$this->seen)) {
				$latestMessage = $this->findLatestMessage();
				$latestMessage->flagged = $this->flagged;
				$latestMessage->seen = $this->seen;
				if(!$latestMessage->save()) {
					throw new Exception("Could not save latest message");
				}
			}
		
			if(($this->isModified('flagged') && !$this->flagged) || ($this->isModified('seen') && $this->seen)) {
				foreach($this->messages as $message) {
					$message->flagged = $this->flagged;
					$message->seen = $this->seen;
					if(!$message->save()) {
						throw new Exception("Could not save message");
					}
				}
			}
		}
		
		return parent::internalSave();
	}
}