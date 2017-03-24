<?php
namespace GO\Modules\GroupOffice\Messages\Model;

use DateTime;
use GO\Core\Accounts\Model\AccountRecord;
use GO\Core\Blob\Model\BlobNotifierTrait;
use GO\Core\Users\Model\User;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Query;

use IFW\Util\StringUtil;
use PDO;
use Sabre\VObject\UUIDUtil;

/**
 * The Message model
 * 
 * This holds all data of an e-mail message. The message is separated from the 
 * IMAP related data because we want to keep this data in Group-Office even if 
 * the message hs been deleted on IMAP.
 * 
 *
 * @property int $ownerUserId
 * @propery int $threadId Each messaqe thread get's a unique thread id. This is the ID of the first message in the thread
 * @property User $owner
 * @property string $date
 * @property Address $from
 * @property Address[] $to
 * @property Address[] $cc
 * @property Address[] $bcc
 * 
 * @property Address[] $addresses All addresses from, to, cc and bcc
 * @property string $contentType
 * @property string $messageId Max 255 chars according to RFC
 * @property string $inReplyTo
 
 * @property int inReplyToId
 * @property Message $inReplyTo
 * 
 * @property boolean $answered
 * 
 * @property Attachment[] $attachments
 * @property Message[] $messages The messages in this thread
 * 
 * 
 * @property Thread $thread The message thread
 * @property \GO\Modules\GroupOffice\Imap\Model\Account $account
 * 
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Message extends \GO\Core\Orm\Record {
	
	/**
	 * Messages from the INBOX folder
	 */
	const TYPE_INCOMING = 0;

	/**
	 * Messages from the sent folder
	 */
	const TYPE_SENT = 1;

	/**
	 * Messages from the drafts folder
	 */
	const TYPE_DRAFT = 2;

	/**
	 * Messages with spam flags or from spam folder
	 */
	const TYPE_JUNK = 3;

	/**
	 * Messages that have been trashed by the user
	 */
	const TYPE_TRASH = 4;

	/**
	 * Messages from folders created by the user.
	 */
	const TYPE_OUTBOX = 5;
	
	
	const TYPE_ACTIONED = 6;
	
	const PRIORITY_LOW = 'low';
	const PRIORITY_NORMAL = 'normal';
	const PRIORITY_HIGH = 'high';
	
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * Reference to the thread model.
	 * @var int
	 */							
	public $threadId;
	
	/**
	 * The account this message belongs to.
	 * 
	 * @var int 
	 */
	public $accountId;

	/**
	 * 
	 * @var bool
	 */							
	public $seen = false;

	/**
	 * 
	 * @var bool
	 */							
	public $forwarded = false;

	/**
	 * 
	 * @var bool
	 */							
	public $flagged = false;

	/**
	 * Actioned means that no action is required anymore on this message.
	 * @var bool
	 */							
	public $actioned = false;

	/**
	 * See TYPE_* constants
	 * @var int
	 */							
	public $type = 2;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $modifiedAt;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * Max 255 chars according to RFC
	 * @var string
	 */							
	public $uuid;

	/**
	 * 
	 * @var int
	 */							
	public $inReplyToId;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $sentAt;

	/**
	 * 
	 * @var string
	 */							
	public $subject;

	/**
	 * 
	 * @var string
	 */							
	protected $body;

	/**
	 * 
	 * @var string
	 */							
	public $priority = self::PRIORITY_NORMAL;

	/**
	 * 
	 * @var string
	 */							
	public $photoBlobId;

	use BlobNotifierTrait;
	
	private $isAnswered;
	
	
	/**
	 * Save changes to IMAP too
	 * 
	 * @var boolean 
	 */
//	public $saveToImap = false;
	
	protected static function defineRelations() {		
		//self::hasOne('message', Message::class, ['messageId' => 'id']);		
		self::hasOne('thread', Thread::class, ['threadId' => 'id']);		
		self::hasOne('account', \GO\Modules\GroupOffice\Imap\Model\Account::class, ['accountId' => 'id']);
		self::hasOne('coreAccount', \GO\Core\Accounts\Model\Account::class, ['accountId' => 'id']);
		self::hasMany('messages', self::class, ['threadId' => 'threadId']);	
		
		self::hasMany('attachments', Attachment::class, ['id' => 'messageId']);
		self::hasOne('from', Address::class, ['id' => 'messageId'])->setQuery((new Query())->where(['type'=>  Address::TYPE_FROM]));
		self::hasMany('to', Address::class, ['id' => 'messageId'])->setQuery((new Query())->where(['type'=>  Address::TYPE_TO]));
		self::hasMany('cc', Address::class, ['id' => 'messageId'])->setQuery((new Query())->where(['type'=>  Address::TYPE_CC]));
		self::hasMany('bcc', Address::class, ['id' => 'messageId'])->setQuery((new Query())->where(['type'=>  Address::TYPE_BCC]));

		self::hasMany('addresses', Address::class, ['id' => 'messageId']);		
		
		
		self::hasOne('inReplyTo', Message::class, ['inReplyToId' => 'id']);
		self::hasMany('replies', self::class, ['id' => 'inReplyToId']);
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation('coreAccount');
	}	
	
	
	
	/**
	 * Create a new message
	 * 
	 * @example
	 * ```````````````````````````````````````````````````````````````````````````
	 * $message = Message::create($imapAccountId);		
	 * 		
	 * $to = new Address();		
	 * $to->type = Address::TYPE_TO;
	 * $to->personal = 'John Doe';
	 * $to->address = 'email@domain.com';
	 * 		
	 * $message->to[] = $to;$
	 * 
	 * $message->subject = 'test';
	 * $message->body = 'test';
	 * 
	 * //to send
	 * $message->type = Message::TYPE_OUTBOX;
	 * 
	 * $message->save();
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $accountId 
	 * @param string $subject
	 * @param string $body
	 * @return \self
	 */
	
	public static function create($accountId) {
		
		
		$message = new self;
		$message->thread = new \GO\Modules\GroupOffice\Messages\Model\Thread();
		$message->accountId = $message->thread->accountId = $accountId;
		$address = $message->thread->account->getAccountRecord()->getFromAddress();
		$message->from = $address;
		
		$message->photoBlobId = GO()->getAuth()->sudo(function() use ($address)  {
			$id = \GO\Modules\GroupOffice\Contacts\Model\Contact::find(
					(new Query())
					->select('photoBlobId')
					->joinRelation('emailAddresses')
					->where(['!=',['photoBlobId' => null]])
					->where(['emailAddresses.email' =>$address->address])
					->groupBy(['t.id'])
					->fetchMode(\PDO::FETCH_COLUMN, 0)
					)->single();
			
			return empty($id) ? null : $id;
		}, $message->thread->account->createdBy);
				
		return $message;
	}
	
	
	/**
	 * Parse the given template and set the subject, body and attachments from
	 * the template.
	 * 
	 * @todo attachments
	 * @param \GO\Core\Templates\Model\Message $templateMessage
	 * @return $this
	 */
	public function setTemplate(\GO\Core\Templates\Model\Message $templateMessage, \IFW\Template\VariableParser $parser) {
		
		$this->body = $parser->parse($templateMessage->body);		
		
		$this->subject = $parser->parse($templateMessage->subject);
		
		return $this;
	}
	
	
	
	public function getAnswered() {
		//cache for multiple calls
		if(!isset($this->isAnswered)) {
			$this->isAnswered =self::find(['inReplyToId'=>$this->id])->single() !== false;
		}
		
		return $this->isAnswered;
	}
	
	
	protected function internalSave() {	
		
		$this->saveBlob('photoBlobId');	
					
		//caused problems with modified messages after sync (sync loop)
		$syncThread = $this->isNew() && $this->isModified('thread');
		
		if(!parent::internalSave()) {
			return false;
		}
		
		if($syncThread) {
			return $this->thread->sync();
		}  else {
			return true;
		}
	}
	
	private function embedPastedDataUris(){
		
		if(!isset($this->body)) {
			return;
		}
		
		$regex = '/src="data:image\/([^;]+);([^,]+),([^"]+)/';
		$body = $this->body;
		preg_match_all($regex, $body, $allMatches,PREG_SET_ORDER);
		
		
		foreach($allMatches as $matches){
			if($matches[2]=='base64'){
				$extension = $matches[1];
				
				$attachment = new Attachment();
				$attachment->generateContentId();
				$attachment->name = 'pasted-image';
				
				$tmpFile = GO()->getAuth()->getTempFolder()->getFile(uniqid().'.'.$extension);
				$tmpFile->putContents(base64_decode($matches[3]));
				
				$attachment->setFile($tmpFile);
				
				$this->attachments[] = $attachment;

				$body = str_replace($matches[0],'src="cid:'.$attachment->contentId, $body);
			}
		}
		
		$this->body = $body;
	}
		
	protected function init() {
		parent::init();
		
		if($this->isNew()) {
			$this->sentAt = new DateTime();
		}
	}
	public function setTo($v) {
		foreach($v as $address) {					
			$address = $this->addresses->add($address);
			$address->type = Address::TYPE_TO;
		}		
	}
	
	
	
	/**
	 * Gets the body in HTML. Quoted replies are stripped.
	 * 
	 * The body can be stored in this record but it can also be set to null by the
	 * account when the message is stored on the account to save space.
	 * 
	 * In that case the account is looked up and the body is requested from the
	 * account.
	 * 
	 * @param string
	 */
	public function getBody($replaceImages = true){		
		
		//cast to string as db value is null with text fields
		$html = $this->body;		
		
		if(!isset($html)) {				
			$html = $this->account->getBody($this);			
		}	
		
		if($replaceImages) {
			foreach($this->attachments as $attachment){
				if($attachment->contentId) {				
					$html = str_replace('cid:'.trim($attachment->contentId,'<>'), $attachment->getUrl(), $html, $count);
				}
			}
		}
		return $html;
	}
	
	public function setBody($body) {
		$this->body = $body;
	}
//	
//	/**
//	 * Get's the quoted text if found
//	 * 
//	 * @param string
//	 */
//	public function getQuote(){
//		$html = (string) $this->getAttribute('quote');
//
//		foreach($this->attachments as $attachment){
//			if($attachment->contentId) {
//				$html = str_replace('cid:'.trim($attachment->contentId,'<>'), $attachment->getUrl(), $html, $count);
////				if($count && !$attachment->foundInBody){
////					$attachment->foundInBody = true;
////					$attachment->save();
////				}
//			}
//		}
//		return $html;
//	}
//	
//	
	
	/**
	 * @todo images on replies
	 */
	private function replaceImages() {
		
		if(!isset($this->body)) {
			return;
		}
		
		$body = $this->body;
		foreach ($this->attachments as $attachment) {

			if(isset($attachment->src)) {
				//Find the image in the body. It might be that it's not found. This can 
				//happen when an inline image was attached but deleted in the editor 
				//afterwards.
				$regex = '/="([^"]*' . preg_quote($attachment->src, '/') . '[^"]*)"/';

	//			GO()->debug("Finding inline image: " . $src . " with regex " . $regex);

				$result = preg_match($regex, $body, $matches);
				if ($result) {

					GO()->debug("Found image src: " . $matches[1]);

					$attachment->src = null;
					$attachment->generateContentId();

					//$tmpFile->delete();								
					$body = StringUtil::replaceOnce($matches[1], 'cid:'.$attachment->contentId, $body);
				} else {
					GO()->debug("Image NOT found");
	//					throw new \Exception("Image NOT found");
				}
			}
		}
		
		$this->body = $body;
	}

	/**
	 * Get's a small part in plain text of the body
	 * 
	 * @param int $length
	 * @param string
	 */
	public function getExcerpt($length = 70){
		
//		if(isset($this->_body)){
			$text = str_replace('>','> ', $this->getBody(false));		
			
			$text = strip_tags($text);			
			$text = html_entity_decode($text);			
			
			$text = trim(preg_replace('/[\s]+/u',' ', $text));			
			$text = StringUtil::cutString($text, $length, true);			
//		}else
//		{
//			$text = null;
//		}
		
		return $text;
		
	}
	
	
	protected function internalValidate() {
		
		if(!isset($this->accountId) && $this->isModified("thread")) {
			$this->accountId = $this->accountId = $this->thread->accountId;							
		}
		
//		if($this->isModified('body') && strlen($this->body) > 1024*1024){
//			$this->body = substr($this->getAttribute('body', 0, 1024*1024));
//		}
		
		if($this->isNew() && !isset($this->uuid)) {
			$this->uuid = UUIDUtil::getUUID().'@'.GO()->getEnvironment()->getHostname();
		}
		
		if(!parent::internalValidate()) {
			return false;
		}
		
//		if(!$this->validateRecipients()) {
//			return false;
//		}
		
		$this->replaceImages();
		$this->embedPastedDataUris();
		
		
		return true;
		
	}

	/**
	 * 
	 * @todo Must be validated when created with client but a message coming from imap may have no recipients
	 */
	private function validateRecipients() {
		
		//only check if new or the addresses have been modified
		if($this->isNew() || $this->isModified('addresses')) {			
			foreach($this->addresses as $address) {
				if($address->type != Address::TYPE_FROM) {
					return true;
				}
			}
			
			$this->setValidationError('to', 'norecipient');
			return false;			
		}
		
	}
	
}

