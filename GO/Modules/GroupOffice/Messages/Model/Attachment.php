<?php

namespace GO\Modules\GroupOffice\Messages\Model;

use GO\Core\Blob\Model\Blob;
use GO\Core\Blob\Model\BlobNotifierTrait;
use GO\Core\Blob\Model\TransportUtil;
use GO\Core\Orm\Record;
use Sabre\VObject\Reader;
use stdClass;

/**
 * The Attachment model
 *
 * 
 * 
 * @property-write int $origAttachmentId Can be set to an original attachment ID instead of providing a file as the 
 * contents of the attachment. Usefull for replying and forwarding.
 * 
 * @property Message $message
 * @property string $src  When a new image is inserted in the composer then this is set to the src attribute
 * of the img tag.
 * This src will be looked up and replaced by the generated contentId
 * 
 * eg. /api/upload/tempfile.jpg -> cid:122334@hostname
 * 
 *
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Attachment extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $messageId;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * If this set then this file (image) appears inline in the message body. When it's an attachment this is set to null. 
	 * @var string
	 */							
	public $contentId;

	/**
	 * 
	 * @var int
	 */							
	public $size;

	/**
	 * 
	 * @var string
	 */							
	public $blobId;

	/**
	 * 
	 * @var string
	 */							
	public $contentType;

	use BlobNotifierTrait;
	
	private $src;
	
	/**
	 * The blob object is stored here when it is created
	 * @var Blob 
	 */
	private $blob;

	public function setOrigAttachmentId($id) {
		$attachment = Attachment::findByPk($id);

		if(!$attachment) {
			throw new \Exception("Could not find attachment to copy from!");
		}
		
		$blob = $attachment->createBlob();
		
		$this->blobId = $blob->blobId;
		$this->contentType = $blob->contentType;
	}

	public function setSrc($src) {
		//make url relative because browser may alter it:				
		$this->src = preg_replace('/http(s)?:\/\/[^\/]+\//', '', $src);
	}

	public function getSrc() {
		return $this->src;
	}

	/**
	 * If this is an iCalendar attachment then read it and parse its properties
	 * 
	 * @return string
	 */
	public function getEvent() {
		if($this->contentType !== 'text/calendar') {
			return new stdClass; //none scalar doesn't get returned to client
		}
		if($this->name != 'invite.ics') {
			return;
		}
		
		$vobject = Reader::read(
			\IFW\Util\StringUtil::cleanUtf8(file_get_contents($this->createBlob()->getPath())),
			Reader::OPTION_FORGIVING
		);
		
		$event = $vobject->VEVENT;

		if(!empty($event)) {
			$properties = ['METHOD' => (string)$vobject->METHOD];
			$properties['ATTENDEE'] = [];
			foreach($event->children() as $child) {
				if($child->name == 'ATTENDEE') {
					$properties[$child->name][] = [(string)$child,(string)$child['CN']];
				} else if($child->name == 'ORGANIZER') {
					$properties[$child->name][] = [(string)$child, (string)$child['CN']];
				} else {
					$properties[$child->name] = (string)$child;
				}
			}
			return $properties;
		}
	}

	protected static function defineRelations() {
		self::hasOne('message', Message::class, ['messageId' => 'id']);
		self::hasOne('imapMessageData', ImapMessageData::class, ['messageId' => 'id']);
	}

	public static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\Everyone();
	}
	
	protected function internalValidate() {
		
		if(isset($this->blobId) && !isset($this->contentType)) {
			$this->contentType = Blob::findByPk($this->blobId)->contentType;
		}
		
		return parent::internalValidate();
	}

	public function internalSave() {

		$this->saveBlob('blobId');

		$success = parent::internalSave();

		if($this->isNew() && $this->contentType === 'text/calendar') {
			$this->fireEvent('newIcsAttachment', $this->getEvent('vobject'));
		}
		
		return $success;
	}
	
	/**
	 * 
	 * @return Blob
	 */
	public function createBlob() {
		
		if(isset($this->blob)) {
			return $this->blob;
		}
		
		if(isset($this->blobId)) {
			$blob = Blob::findByPk($this->blobId);
			
			if($blob) {
				$this->blob = $blob;
			}
			
			return $blob;
			
		}
		
		$account = $this->message->account;	

		$blob = $account->getAttachmentBlob($this);		
		if($blob) {
			$this->blob = $blob;
		}
		
		$this->blobId = $blob->blobId;
		$this->save();

		return $blob;
	}	
	

	/**
	 * Output the file to the browser for download
	 */
	public function output() {
		$blob = $this->createBlob();
		TransportUtil::download($blob);
	}

	
	public function getUrl() {
		
		if($this->isNew()) {
			return null;
		}
		
		$params = isset($this->contentId) ? ['cid' => $this->contentId] : [];
		
		return GO()->getRouter()->buildUrl('messages/' . $this->message->id . '/attachments/' . $this->id, $params);
	}

	/**
	 * Generate's a new contentId and sets it.
	 */
	public function generateContentId() {
		if(!isset($this->contentId)) {		
			$this->contentId = uniqid() . '@' . GO()->getEnvironment()->getHostname();
		}
	}

}
