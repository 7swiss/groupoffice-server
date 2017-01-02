<?php

namespace GO\Modules\GroupOffice\Imap\Model;

use IFW\Orm\Record;


/**
 * Used for delayed fetching of attachment data
 *
 * 
 * @property  \GO\Modules\GroupOffice\Messages\Model\Attachment $attachment
 * @property ThreadMessage $imapMessage
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Attachment extends Record {	
	/**
	 * 
	 * @var int
	 */							
	public $attachmentId;

	/**
	 * 
	 * @var int
	 */							
	public $messageId;

	/**
	 * The IMAP part number
	 * @var string
	 */							
	public $partNo;

	/**
	 * 
	 * @var string
	 */							
	public $encoding;

	protected static function defineRelations() {
		parent::defineRelations();
		
		self::hasOne('attachment', \GO\Modules\GroupOffice\Messages\Model\Attachment::class, ['attachmentId'=>'id']);
		self::hasOne('imapMessage', Message::class, ['messageId'=>'messageId']);
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('imapMessage');
	}
	
	
	/**
	 * Get the blob for this attachment. The blob will expire in one month.
	 * @return Blob
	 */
	public function getBlob() {
		$mailbox = $this->imapMessage->folder->getImapMailbox();
		
		$message = $mailbox->getMessage($this->imapMessage->imapUid, true);
		
		$tempFile = GO()->getAuth()->getTempFolder()->getFile(uniqid());
		
		$streamer = new \IFW\Imap\Streamer(fopen($tempFile->getPath(), 'w+'), $this->encoding);
		
		$message->fetchPartData($this->partNo, true, $streamer);		
		
		$expireAt = new \DateTime(Account::EXPIRE_INTERVAL_ATTACHMENTS);
		
		return \GO\Core\Blob\Model\Blob::fromFile($tempFile, $expireAt);
	}
}
