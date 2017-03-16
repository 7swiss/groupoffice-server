<?php
namespace GO\Modules\GroupOffice\Imap\Model;

use DateTime;
use Exception;
use GO\Core\Accounts\Model\AccountRecord;
use GO\Core\Accounts\Model\SyncableInterface;
use GO\Core\Blob\Model\Blob;
use GO\Core\Smtp\Model\Account as SmtpAccount;
use GO\Modules\GroupOffice\Messages\Model\Address;
use GO\Modules\GroupOffice\Messages\Model\Attachment as MessagesAttachment;
use GO\Modules\GroupOffice\Messages\Model\Message as MessagesMessage;
use GO\Modules\GroupOffice\Messages\Model\Thread;
use Html2Text\Html2Text;
use IFW;
use IFW\Imap\Connection;
use IFW\Imap\Mailbox;
use IFW\Orm\Query;
use PDO;
use Swift_Attachment;
use Swift_Message;
use Swift_Mime_ContentEncoder_Base64ContentEncoder;

/**
 * The Account model
 *
 * 
 * @property SmtpAccount $smtpAccount
 * @properry Signature[] $signatures
 * @properry Folder[] $folders
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Account extends AccountRecord implements SyncableInterface{

	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var string
	 */							
	public $username;

	/**
	 * 
	 * @var string
	 */							
	public $password;

	/**
	 * 
	 * @var string
	 */							
	public $hostname;

	/**
	 * 
	 * @var int
	 */							
	public $port = 143;

	/**
	 * 
	 * @var string
	 */							
	public $encryption = 'tls';

	/**
	 * 
	 * @var int
	 */							
	public $smtpAccountId;

	/**
	 * 
	 * @var int
	 */							
	public $createdBy;

	/**
	 * Stored attachments blob are cleaned up after one month.
	 */
	const EXPIRE_INTERVAL_ATTACHMENTS = '+1 month';
	
	public $trashFolder = 'Trash';
	
	public $sentFolder = 'Sent';
	
	public $draftsFolder = 'Drafts';
	
	public $spamFolder = 'Spam';
	
	public $actionedFolder = 'Actioned';
	
	/**
	 *
	 * @var Connection 
	 */
	private static $connections;
	
	protected static function defineRelations() {
		
		self::hasOne('smtpAccount', SmtpAccount::class, ['smtpAccountId' => 'id']);
		self::hasMany('signatures', Signature::class, ['id' => 'accountId']);
		
		self::hasMany('folders', Folder::class, ['id'=>'accountId'])->setQuery(['parentFolderId'=>null]);
		
		
		parent::defineRelations();
	}
	
	public static function getDefaultReturnProperties() {
		return parent::getDefaultReturnProperties().',smtpAccount,signatures';
	}
	
	public function toArray($properties = null) {
		
		$attr = parent::toArray($properties);
		
		unset($attr['password']);
		
		return $attr;
					
	}
	
//	protected function internalDelete($hard) {
//		
//		//if(\GO\Modules\GroupOffice\Messages\Model\Attachment::find(['']))
//		
//		return parent::internalDelete($hard);
//	}

	public function getName() {
		return $this->username;
	}
	
	public function internalValidate() {	
		if(!parent::internalValidate()){
			return false;
		}
		
		if($this->isModified(['hostname', 'port', 'username', 'password', 'encryption'])) {
			try {
				$this->connect();
			}
			catch(\Exception $e) {
				$this->setValidationError('hostname', "connecterror", ['exception' => $e->getMessage()]);

				return false;
			}
		}
//		if($this->hostname != 'imap.gmail.com') {
//			$this->sentItemsFolderName = 'Sent';
//		}
		
		return true;
	}
	
	
	/**
	 * Get the IMAP connection
	 * 
	 * @return Connection
	 */
	public function connect() {

		if (!isset(self::$connections[$this->id])) {
			self::$connections[$this->id] = new Connection();
			
//			$this->connection->debug = GO()->getDebugger()->enabled;
			
			if(!self::$connections[$this->id]->connect($this->hostname, $this->port, $this->encryption == 'ssl')){
				throw new Exception($this->connection->connectError, $this->connection->connectErrorNo);			
			}
			
			if($this->encryption == 'tls' && !self::$connections[$this->id]->startTLS()) {
				throw new Exception("Could not enable TLS encryption");
			}
		}

		if (!self::$connections[$this->id]->isAuthenticated() && !self::$connections[$this->id]->authenticate($this->username, $this->password)) {
			throw new Exception("Could not authenticate to hostname " . $this->hostname.' : '.$this->connection->lastCommandStatus);
		}

		return self::$connections[$this->id];
	}

	public function sync(){
		
//		GO()->getDbConnection()->query("DELETE FROM messages_message");
		
		GO()->debug("Start sync account ".$this->id);		
		
//		$notification = new \GO\Core\Notifications\Model\Notification();
//		$notification->type = 'sync';		
//		$notification->data = $this->toArray('id,name');
//		$notification->record = $this;		
//		$notification->save();
		
		
		
		if(!GO()->getProcess()->lock('account-sync-'.$this->id)) {
			throw new \Exception("Sync already in progress");
		}		
	
		$folders = $this->syncFolders();
		
		GO()->getProcess()->setProgress(null);
		
		$newCount = 0;
		
		foreach($folders as $folder) {
			
			GO()->debug("Sync ".$folder->name);
			$newCount += $folder->sync();
		}
		
		GO()->debug("Start thread sync");
		
		
		
//		if($newCount) {
			//only process threading stuff when new messages have arrived
//			$this->updateReplies();				
			$this->thread();
			Thread::syncAll($this->id);
//		}
		
		GO()->debug("Sending messages");
		
		$this->sendMessages();
		
		GO()->getProcess()->setProgress(100);
		
//		$this->notify();
		
		return [];
	}
	
	private function thread() {
			
		$messages = MessagesMessage::find(
						(new Query())						
						->where(['accountId'=>$this->id, 'threadId'=>null])
						->orderBy(['sentAt' => 'DESC'])
						);
		
		foreach($messages as $message) {
			GO()->debug("Thread for message ID: ".$message->id);
			
			
			if(!isset($message->inReplyToId)) {
				GO()->debug("Lookup inReplyToId");
				$imapMessage = Message::find(['messageId' => $message->id, 'accountId' => $this->id]);
				if(isset($imapMessage->inReplyToUuid)) {
					$reply = MessagesMessage::find(['uuid' => $imapMessage->inReplyToUuid, 'accountId' => $this->id]);
					if($reply) {
						GO()->debug("Found inReplyToId: ".$reply->id);
						$message->inReplyToId = $reply->id;
					}
				}
			}
			
			GO()->debug("Lookup related message");
			
			//this will find threads from messsages that were sent later then the 
			//current message because we're going from new to old messaqes
			$relatedStore = MessagesMessage::find(
							(new Query())
							->fetchSingleValue('threadId')							
							->joinRelation('imapMessage.references')							
							->andWhere(['accountId'=>$this->id])
							->andWhere(['!=',['threadId' => null]])
							->andWhere('references.uuid=:tmid')->bind(':tmid',$message->uuid)
							);
			$threadId = $relatedStore->single();
			
			if(!$threadId) {
				//try to find an older thread
				$refs = MessageReference::find((new Query)->fetchSingleValue('uuid')->andWhere(['messageId' => $message->id]))->all();
				
				if(count($refs)) {
					$relatedStore = MessagesMessage::find(
								(new Query())
								->fetchSingleValue('threadId')
								->andWhere(['accountId'=>$this->id])
								->andWhere(['!=',['threadId' => null]])
								->andWhere(['uuid' => $refs])
								);
					$threadId = $relatedStore->single();
				}
			}
			
			if($threadId) {
				GO()->debug("Found existing thread ".$threadId);
				$message->threadId = $threadId;
			}else
			{
				GO()->debug("Creating new thread");
				
				$message->thread = new Thread();			
				$message->thread->setLatestMessage($message, false);
				$message->thread->accountId = $this->id;				
			}
			
			$message->modifiedAt = new \IFW\Util\DateTime();
			
			if($message->imapMessage) {
				$message->imapMessage->syncedAt = $message->modifiedAt; //make sure they exactly match
				
				GO()->debug("Applying tags from imap folder");
				$message->thread->tags = $message->imapMessage->folder->toTags();	
			}
			
			
			if (!$message->save()) {
				throw new Exception("Failed to save message: ".var_export($message->getValidationErrors(), true));
			}
			
			GO()->debug("Done with thread on message ID: ".$message->id);
		}
	}
	
	/**
	 * Set's all the right in reply to ID's
	 */
//	private function updateReplies() {
//		
//		GO()->debug("Updating inReplyToId");
//		$sql = "update messages_message t
//	inner join imap_message it on it.messageId=t.id
//	inner join imap_folder f on it.folderId = f.id
//    inner join messages_message reply on reply.uuid = it.inReplyToUuid    
//    set t.inReplyToId = reply.id
//    where t.inReplyToId is null and f.accountId=".$this->id;
//		
//		GO()->getDbConnection()->query($sql);
//		
//		GO()->debug("Done updating inReplyToId");
//	}
	
//	private function notify() {
//		$query = (new Query)
//						->select('count(*)')
//						->fetchMode(\PDO::FETCH_COLUMN, 0)
//						->where(['accountId'=>$this->id, 'seen'=>false]);
//		
//		$count = (int) Thread::find($query)->single();
//		
//		$key = ['name'=>'unseen', 'recordTypeId' => $this->getRecordType()->id, 'recordId'=>$this->id];
//		
//		$event = \GO\Core\Log\Model\Event::find($key)->single();
//		
//		if(!$event) {
//			$event = new \GO\Core\Log\Model\Event();
//			$event->setValues($key);
//			$event->notifications[] = ['userId'=>$this->coreAccount->createdBy];
//		}  else {
//			$event->createdAt = new \DateTime();
//			$sql = "UPDATE ".\GO\Core\Log\Model\Notification::tableName()." SET seenAt=null WHERE eventId=:eventId";
//			$stmt = GO()->getDbConnection()->getPDO()->prepare($sql);
//			
//		}
//		$event->data = ['unseen' => $count, 'name'=>$this->name];
//		if(!$event->save()) {
//			throw new \Exception("Could not save event!");
//		}
//		
//		
//	}
	
	private function sendMessages() {
		$messages = MessagesMessage::find(
						(new IFW\Orm\Query())						
						->joinRelation('thread')						
						->where([
								'thread.accountId'=>$this->id,
								'type'=>  MessagesMessage::TYPE_OUTBOX
										])
						);
		
		foreach($messages as $message) {
			$swiftMessage = $this->sendMessage($message);
			if($swiftMessage) {
				$this->saveToSent($swiftMessage, $message);
			}
		}
	}

	/**
	 * 
	 * @param MessagesMessage $message
	 * @return Swift_Message|boolean
	 * @throws Exception
	 */
	private function sendMessage(MessagesMessage $messagesMessage) {
		
		GO()->debug("Sending ".$messagesMessage->subject);
		
		$message = (new \GO\Core\Email\Model\Message($this->smtpAccount, $messagesMessage->subject))
				->setFrom($messagesMessage->from->address, $messagesMessage->from->personal);
		
		
		//Override qupted-prinatble encdoding with base64 because it uses much less memory on larger bodies. See also:
		//https://github.com/swiftmailer/swiftmailer/issues/356
		$message->setEncoder(new Swift_Mime_ContentEncoder_Base64ContentEncoder());

		foreach ($messagesMessage->to as $address) {
			$message->addTo($address->address, $address->personal);
		}
		
		foreach ($messagesMessage->cc as $address) {
			$message->addCc($address->address, $address->personal);
		}
		
		foreach ($messagesMessage->bcc as $address) {
			$message->addBcc($address->address, $address->personal);
		}
		
		//use get attribute here so images are not replaced
		$message->setBody($messagesMessage->getBody(false), 'text/html');			

		//add converted text body
		$html = new Html2Text($messagesMessage->body);			
		$part = $message->addPart($html->getText());

		//Override qupted-prinatble encdoding with base64 because it uses much less memory on larger bodies. See also:
		//https://github.com/swiftmailer/swiftmailer/issues/356
		$part->setEncoder(new Swift_Mime_ContentEncoder_Base64ContentEncoder());
		

		
		foreach ($messagesMessage->attachments as $attachment) {
			$blob = Blob::findByPk($attachment->blobId);
			
			$swiftAttachment = \GO\Core\Email\Model\Attachment::fromPath($blob->getPath(), $blob->contentType);
			$swiftAttachment->setFilename($attachment->name);
			
			if(isset($attachment->contentId)) {
				$swiftAttachment->setId($attachment->contentId);
				$swiftAttachment->setDisposition('inline');				
			}
			
			$message->attach($swiftAttachment);
		}
		
		$messageId = trim($messagesMessage->uuid, '<>');
		$message->setId($messageId);
		
		//handle reply headers		
		$inReplyTo = $messagesMessage->inReplyTo;
		if($inReplyTo) {
			
			$headers = $message->getHeaders();
			
			$refs = clone $messagesMessage->messages;
			$refs->getQuery()
							->distinct()
							->select('uuid')
							->fetchMode(PDO::FETCH_COLUMN, 0)
							->where(['!=',['uuid' => $messagesMessage->uuid]]);
			
			$refArray = $refs->all();
			
			if(!in_array($inReplyTo->uuid, $refArray)) {
				$refArray[] = $inReplyTo->uuid;
			}
			
			$headers->addTextHeader("References", implode(" ", $refArray));			
			$headers->addTextHeader("In-Reply-To", $inReplyTo->uuid);
		
		}
		
		$failures = [];
		//workaround https://github.com/swiftmailer/swiftmailer/issues/335		
		$numberOfRecipients = $message->send($failures);
		if(!$numberOfRecipients ) {
			//throw new \Exception("Failed to send message: ".var_export($failures, true));
			GO()->debug("Failed to send message: ".var_export($failures, true));
			return false;
		}else
		{
			GO()->debug("Sent successfully");
		}
		//The ID changes after send. We set it here again for the save to sent function.
		$message->setId($messageId);
		
		return $message;
	}
	
	
	/**
	 * 	 
	 * @param Swift_Message $swiftMessage
	 * @param MessagesMessage $message
	 * @return Message|boolean
	 * @throws \Exception
	 */
	private function saveToSent( Swift_Message $swiftMessage, MessagesMessage $message) {
			IFW::app()->debug('Save to send');
			
		$sentFolder = Folder::find([
				'accountId' => $this->id, 
				'name' => 'Sent'])
						->single();
		
		if(!$sentFolder) {			
			throw new \Exception("Sent folder not found in db!");
		}

		$sentMailbox = $sentFolder->getImapMailbox();
		if(!$sentMailbox) {
			throw new \Exception("Sent folder not found on IMAP!");
		}		
		$uid = $sentMailbox->appendMessage($swiftMessage, ["\\Seen"]);		
		
		if(!$uid) {
			throw new \Exception("Could not save message on IMAP!");
		}
		
		$message->type = MessagesMessage::TYPE_SENT;
		
		$imapMessage = new Message();
		$imapMessage->folderId = $sentFolder->id;
		$imapMessage->imapUid = $uid;
		$imapMessage->syncedAt = $message->modifiedAt = new DateTime();
		
		$inReplyToHeader = $swiftMessage->getHeaders()->get("In-Reply-To");
		if($inReplyToHeader){		
			$imapMessage->inReplyToUuid = $inReplyToHeader->getFieldBody();
		}		
		
		$refs = [$swiftMessage->getId()];
		
		$referencesHeader = $swiftMessage->getHeaders()->get("References");
		if($referencesHeader) {
			$refs = array_merge($refs, explode(' ', $referencesHeader->getFieldBody()));	
			$refs = array_unique($refs);
			
			$refs = array_map(function($ref) {
				return trim($ref, '<>');
			},$refs);
			
		}
		
		

		foreach($refs as $uuid) {
			$tr = new MessageReference();
			$tr->uuid = $uuid;
			$imapMessage->references[] = $tr;
		}
		
		$message->imapMessage = $imapMessage;
		
		//clear body and attachment data because we store it on imap now.		
		$message->body = null;	
		
		if(!$message->save()) {
			throw new \Exception("Could not save thread message");
		}
		
		//set expiry dates on the attachment blobs
		foreach($message->attachments as $attachment) {
			$blob = $attachment->createBlob();
			$blob->expireAt = new DateTime(self::EXPIRE_INTERVAL_ATTACHMENTS);
			$blob->save();
		}
	}
	
	/**
	 * 
	 * @param int $type
	 * @return Folder
	 */
	public function getFolderForType($type) {
		
		$folderName = null;
		switch($type) {
			case MessagesMessage::TYPE_ACTIONED:
				$folderName = $this->actionedFolder;
				break;
			case MessagesMessage::TYPE_DRAFT:
				$folderName = $this->draftsFolder;
				break;
			case MessagesMessage::TYPE_INCOMING:
				$folderName = "INBOX";
				break;
			case MessagesMessage::TYPE_JUNK:
				$folderName = $this->spamFolder;
				break;
			case MessagesMessage::TYPE_OUTBOX:
				return null;
				
			case MessagesMessage::TYPE_SENT:
				$folderName = $this->sentFolder;
				break;
			case MessagesMessage::TYPE_TRASH:
				$folderName = $this->trashFolder;
				break;
		}
		
		return Folder::find(['accountId' => $this->id, 'name' => $folderName])->single();
	}
	
	
	/**
	 * 
	 * @return Folder
	 */
	private function syncFolders($parentMailbox = null, $parentFolder = null) {
	
		$createDefault = false;
		if(!isset($parentMailbox)) {
			$parentMailbox = new Mailbox($this->connect());
			
			$createDefault = true;
		}
		
//		$this->connect()->debug = true;

		$folders = [];
		$mailboxes = $parentMailbox->getChildren();
		GO()->debug("Folders fetched from IMAP");
		foreach ($mailboxes as $mailbox) {
			
			GO()->debug("Sync IMAP folder: ".$mailbox);
				
			$folder = Folder::find(['name' => $mailbox->name, 'accountId' => $this->id])->single();

			if (!$folder) {
				$folder = new Folder();
				$folder->accountId = $this->id;
				$folder->name = $mailbox->name;
				if(!$mailbox->noSelect){
					$folder->uidValidity = $mailbox->getUidValidity();
				}				
			}
			
			$folder->delimiter = $mailbox->delimiter;
			
			if(isset($parentFolder)){
				$folder->parentFolderId = $parentFolder->id;
			}
			
			if(!$mailbox->noSelect){
				if (!isset($folder->highestModSeq)) {
					$folder->highestModSeq = $mailbox->getHighestModSeq();
				}
				if ($folder->uidValidity != $mailbox->getUidValidity()) {
					//UID's not valid anymore! Set all uid's to null.	
					//Also set folderId = null. This way we can also detect moves of mail because we search by messageId
					
					GO()->getDbConnection()->query('DELETE FROM imap_message WHERE folderId='.$folder->id.')');
//					GO()->dbConnection()->query('UPDATE emailMessage SET imapUid=null, folderId=null WHERE folderId=' . $folder->id);

					GO()->debug('UID\'s not valid anymore for folder: ' . $folder->name, 'imapsync');
				}

				$folder->uidValidity = $mailbox->getUidValidity();
			}

			if(!$folder->save()) {
				throw new \Exception("Failed to save folder '".$folder->name."' validation: ".var_export($folder->getValidationErrors(), true));
			}

			$folders[$folder->name] = $folder;
			
			$folders = array_merge($folders, $this->syncFolders($mailbox, $folder));			
		}
		
		if($createDefault && $this->createDefaultFolders($folders)) {
			return $this->syncFolders($parentMailbox);			
		}
		
		//delete folders that don't exist on IMAP.
		$currentFolders = !isset($parentFolder) ? $this->folders : $parentFolder->folders;		
		foreach($currentFolders as $folder) {
			if(!in_array($folder->name, $mailboxes)){
				$folder->delete();
			}
		}

		return $folders;
	}
	
	private function createDefaultFolders($folders) {
		$changes = false;
			
		if(!isset($folders["INBOX"])) {
			$inbox = Mailbox::findByName($this->connect(), 'INBOX');
			$inbox->subscribe();
			$changes = true;
		}

		if(!isset($folders[$this->actionedFolder])) {
			$conn = $this->connect();
			$conn->createMailbox($this->actionedFolder);			
			$changes = true;
		}

		if(!isset($folders[$this->sentFolder])) {
			$conn = $this->connect();
			$conn->createMailbox($this->sentFolder);			
			$changes = true;				
		}

		if(!isset($folders[$this->draftsFolder])) {
			$conn = $this->connect();
			$conn->createMailbox($this->draftsFolder);			
			$changes = true;				
		}

		if(!isset($folders[$this->trashFolder])) {
			$conn = $this->connect();
			$conn->createMailbox($this->trashFolder);			
			$changes = true;				
		}

		return $changes;			
	}
	
	
	public function getBody(MessagesMessage $message) {
		
		if($message->isNew()) {
			return null;
		}
		
		$imapMessageRecord = Message::find(['messageId' => $message->id])->single();
		if(!$imapMessageRecord) {
			return "";
		}
		
		$imapMessage = $imapMessageRecord->folder->getImapMailbox()->getMessage($imapMessageRecord->imapUid, true);
		
		return $imapMessage->getBody();					
		
//		$message->
		
	}
	
	public function getAttachmentBlob(MessagesAttachment $attachment) {		
		return Attachment::find(['attachmentId' => $attachment->id])->single()->getBlob();
	}
	
	/**
	 * 
	 * @return Address
	 */
	public function getFromAddress() {
		
		//when account is new
		if(!$this->smtpAccount) {
			return null;
		}
		
		$address = new Address();		
		$address->type = Address::TYPE_FROM;
		$address->address = $this->smtpAccount->fromEmail;
		$address->personal = $this->smtpAccount->fromName;
		
		return $address;
	}

}
