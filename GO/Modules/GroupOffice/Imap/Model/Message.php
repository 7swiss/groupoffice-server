<?php

namespace GO\Modules\GroupOffice\Imap\Model;

use DateTime;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Messages\Model\Address;
use GO\Modules\GroupOffice\Messages\Model\Attachment as MessagesAttachment;
use GO\Modules\GroupOffice\Messages\Model\Message as MessagesMessage;
use IFW\Auth\Permissions\ViaRelation;
use IFW\Imap\Message as ImapMessage;
use IFW\Orm\Query;
use IFW\Orm\Record;
use PDO;

/**
 * The Message model
 * 
 * This extends the message with IMAP data. This model will be deleted when it's
 * deleted on IMAP but the regular message model might be required.
 *
 * @propery string $inReplyToUuid
 * 
 * @property Folder $folder
 * @property Message $message
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Message extends Record {	
	
	/**
	 * 
	 * @var int
	 */							
	public $messageId;

	/**
	 * 
	 * @var int
	 */							
	public $folderId;

	/**
	 * UID of the IMAP server
	 * @var int
	 */							
	public $imapUid;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $syncedAt;

	/**
	 * 
	 * @var string
	 */							
	public $inReplyToUuid;

	protected static function defineRelations() {
		self::hasOne('message', MessagesMessage::class, ['messageId' => 'id']);
		self::hasOne('folder', Folder::class, ['folderId' => 'id']);
		
		self::hasMany('attachments', Attachment::class, ['messageId' => 'messageId']);
		self::hasMany('references', MessageReference::class, ['messageId' => 'messageId']);
		
		MessagesMessage::hasOne('imapMessage', self::class, ['id'=>'messageId']);
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation('message');
	}
	
	
	public function updateMessage(ImapMessage $imapMessage) {
		$this->message->forwarded = $imapMessage->getForwarded();
		$this->message->seen = $imapMessage->getSeen();		
		
		$type = $this->folder->getMessageType();
		$this->message->type = $type;
		
		//make sure timestamps are equal
		$this->syncedAt = $this->message->modifiedAt = new \DateTime();
	}
	
	
	/**
	 * Set's all attributes from an IMAP message
	 * 
	 * @param ImapMessage $imapMessage
	 */
	public function applyMessage(ImapMessage $imapMessage) {		
		
		$uuid = $imapMessage->messageId;		
		if(empty($uuid)) {
			$uuid = $imapMessage->date->format('U');
			
			if(isset($imapMessage->from)) {
				$uuid .= '-'.$imapMessage->from->getEmail();
			}
			
			if(empty($uuid)) {
				$uuid = $imapMessage->uid.'-'.$this->folder->id;
			}
		}
		
		$this->message = new MessagesMessage();
		$this->message->accountId = $this->folder->accountId;
		$this->message->uuid = $uuid;		
		if($imapMessage->xPriority > ImapMessage::XPRIORITY_NORMAL) {
			$this->message->priority = MessagesMessage::PRIORITY_LOW;
		} else if($imapMessage->xPriority < ImapMessage::XPRIORITY_NORMAL) {
			$this->message->priority = MessagesMessage::PRIORITY_HIGH;
		} 
		$this->message->type = $this->folder->getMessageType();					
		$this->message->sentAt = $imapMessage->date;		
		
		if(empty($this->message->sentAt)) {
			$this->message->sentAt = $imapMessage->internaldate;
			
			if(empty($this->message->sentAt)) {
				GO()->debug("No date set for message!");
				$this->message->sentAt = new \DateTime();
			}
		}				
		
		$this->message->subject = $imapMessage->subject;
		
		\IFW\Db\Utils::cutAttributesToColumnLength($this->message);

		if(isset($imapMessage->from)) {
			$this->message->addresses[] = (new Address())->setValues(['personal'=>$imapMessage->from->personal, 'address'=>$imapMessage->from->email, 'type'=>Address::TYPE_FROM]);
		}

		if(isset($imapMessage->to)) {
			foreach($imapMessage->to as $to){
				$this->message->addresses[] = (new Address())->setValues(['personal'=>$to->personal, 'address'=>$to->email, 'type'=>Address::TYPE_TO]);
			}			
		}		

		if(isset($imapMessage->cc)) {
			foreach($imapMessage->cc as $to){
				$this->message->addresses[] = (new Address())->setValues(['personal'=>$to->personal, 'address'=>$to->email, 'type'=>Address::TYPE_CC]);
			}			
		}

		if(isset($imapMessage->bcc)) {
			foreach($imapMessage->bcc as $to){
				$this->message->addresses[] = (new Address())->setValues(['personal'=>$to->personal, 'address'=>$to->email, 'type'=>Address::TYPE_BCC]);
			}			
		}

		$imapAttachments = $imapMessage->getAttachments();
		foreach ($imapAttachments as $attachment) {

			$a = new MessagesAttachment();
			$a->name = $attachment->getFilename();

			if(empty($a->name)){
				$a->name = 'unnamed';
			}
			
			if(!empty($attachment->id)) {// && (strpos($message->body, $attachment->id) || strpos($message->quote, $attachment->id))) {
				$a->contentId = $attachment->id;
			}
			
			$a->contentType = $attachment->getContentType();
			
			$this->message->attachments[] = $a;			
			
			$imapAttachment = new Attachment();
			$imapAttachment->partNo = $attachment->partNumber;
			$imapAttachment->encoding = $attachment->encoding;
			$imapAttachment->attachment = $a;
			
			$this->attachments[] = $imapAttachment;
			
			
		}
		
		//find contact image as account owner
		if(isset($imapMessage->from)) {
			$this->message->photoBlobId = GO()->getAuth()->sudo(function() use ($imapMessage) {
				$id = Contact::find(
						(new Query())
						->select('photoBlobId')
						->joinRelation('emailAddresses')
						->where(['!=',['photoBlobId' => null]])
						->where(['emailAddresses.email' => $imapMessage->from->email])
						->groupBy(['t.id'])
						->fetchMode(PDO::FETCH_COLUMN, 0)
						)->single();

				return empty($id) ? null : $id;
			}, $this->folder->account->createdBy);
		}
		           		
		$this->message->seen = $imapMessage->getSeen();
		$this->message->forwarded = $imapMessage->getForwarded();
		$this->message->flagged = $imapMessage->getFlagged();		
		
		//make sure dates are equal
		$this->syncedAt = $this->message->modifiedAt = new \DateTime();
	}
}