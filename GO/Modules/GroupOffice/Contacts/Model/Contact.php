<?php
/**
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Contacts\Model;

use DateTime;
use GO\Core\Blob\Model\Blob;
use GO\Core\Orm\Record;
use GO\Core\Tags\Model\Tag;
use GO\Core\Users\Model\Group;
use GO\Core\Users\Model\User;
use GO\Core\Users\Model\UserGroup;
use IFW\Orm\Query;

/**
 * The contact model
 *
 * @property int $ownerUserId
 * @property User $owner
 * @property int $groupId The group this contact is accessible too (full access). Set to null to make it a private contact for the owner.
 *
 * @property EmailAddress[] $emailAddresses
 * @property Phone[] $phoneNumbers
 * @property Date[] $dates
 * @property Address[] $addresses
 * @property Contact[] $organizations
 * @property Contact[] $employees
 * @property ContactTag[] $tags
 * @property GO\Core\Blob\Model\Blob $photoBlob The Blob object represending the contact picture
 *
 */
class Contact extends Record {

	/**
	 * The primary key
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * Set to user ID if this contact is a profile for that user
	 * @var int
	 */							
	public $userId;

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
	public $modifiedAt;

	/**
	 * Prefixes like 'Sir'
	 * @var string
	 */							
	public $prefixes = '';

	/**
	 * 
	 * @var string
	 */							
	public $firstName = '';

	/**
	 * 
	 * @var string
	 */							
	public $middleName = '';

	/**
	 * 
	 * @var string
	 */							
	public $lastName = '';

	/**
	 * Suffixes like 'Msc.'
	 * @var string
	 */							
	public $suffixes = '';

	/**
	 * M for Male, F for Female or null for unknown
	 * @var string
	 */							
	public $gender;

	/**
	 * 
	 * @var string
	 */							
	public $notes;

	/**
	 * 
	 * @var bool
	 */							
	public $isOrganization = false;

	/**
	 * name field for companies and contacts. It should be the display name of first, middle and last name
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var string
	 */							
	public $IBAN = '';

	/**
	 * Company trade registration number
	 * @var string
	 */							
	public $registrationNumber = '';

	/**
	 * 
	 * @var string
	 */							
	public $vatNo;

	/**
	 * 
	 * @var string
	 */							
	public $debtorNumber;

	/**
	 * 
	 * @var int
	 */							
	public $organizationContactId;

	/**
	 * 40char blob FK
	 * @var string
	 */							
	public $photoBlobId;

	/**
	 * ;
	 * @var string
	 */							
	protected $language;

	use \GO\Core\Blob\Model\BlobNotifierTrait;

	public static function defineRelations(){
		
		
		self::hasOne('owner', User::class, ['createdBy'=>'id']);
		self::hasMany('emailAddresses', EmailAddress::class, ['id'=>'contactId']);
		
		self::hasMany('tags',Tag::class, ['id'=>'contactId'], true)
						->via(ContactTag::class,['tagId'=>'id']);

		self::hasMany('phoneNumbers', Phone::class, ['id'=>'contactId']);
		
		self::hasMany('addresses', Address::class, ['id'=>'contactId']);
		
		self::hasMany('urls', Url::class, ['id'=>'contactId']);
		
		self::hasMany('dates', Date::class, ['id'=>'contactId']);
		
		self::hasMany('employees', Contact::class, ['id'=>'organizationContactId'])
						->via(ContactOrganization::class, ['contactId' => 'id']);
		
		self::hasMany('organizations', Contact::class, ['id' => 'contactId'])
						->via(ContactOrganization::class, ['organizationContactId' => 'id'])
						->setQuery((new Query())->orderBy(['name'=>'ASC']));
		
		self::hasOne('user', User::class, ['userId' => 'id']);		
		
		User::hasOne('contact', Contact::class, ['id'=>'userId']);
		
		self::hasOne('customfields', CustomFields::class, ['id' => 'id']);		
		
		self::hasMany('groupUsers', UserGroup::class, ['id' => 'contactId'])
						->via(ContactGroup::class, ['groupId'=>'groupId']);
		
		
		self::hasMany('groupPermissions', ContactGroup::class, ['id' => 'contactId']);

		self::hasOne('photoBlob', Blob::class, ['photoBlobId' => 'blobId']);
				
		parent::defineRelations();
	}
	
//	protected function init() {
		
//		if(!isset($this->photoBlobId)) {
//			$blob = \GO\Core\Blob\Model\Blob::createFromFile(new File(dirname(dirname(__FILE__)).'/Resources/male.png'));
//			$this->photoBlobId = $blob->blobId;			
//		}
		
//		parent::init();
//	}
		
	public function internalValidate() {
		
		//If groupId is set to null then make this a private contact for the owner
//		if(!isset($this->groupId)) {
//			$this->groupId = $this->owner->group->id;
//		}
//		
		//always fill name field on contact too
		if(!isset($this->name) && !$this->isOrganization){
			$this->name = $this->firstName;
			
			if(!empty($this->middleName)){
					$this->name .= ' '.$this->middleName;
			}
			
			$this->name .= ' '.$this->lastName;
		}
		
		return parent::internalValidate();
	}
	
	
	public function internalSave() {

		
		//When the contact is copied then the groupPermissions relation is copied as well.
		$createPermissions = $this->isNew() && !$this->isModified('groupPermissions');
		
		
		$this->saveBlob('photoBlobId');
		
		if($this->userId && $this->isModified('photoBlobId')) {
			$this->user->photoBlobId = $this->photoBlobId;
			
			GO()->getAuth()->sudo(function() {
				$this->user->save();
			});
		}

		if(!parent::internalSave()){			
			return false;
		}		
		
		if($createPermissions) {
//			var_dump($this);
			$cg = new ContactGroup();
			$cg->contactId = $this->id;
			$cg->groupId = $this->owner->group->id;			
			if(!$cg->save()) {
				return false;
			}
			
			$cg = new ContactGroup();
			$cg->contactId = $this->id;
			$cg->groupId = Group::ID_EVERYONE;			
			if(!$cg->save()) {
				return false;
			}
		}
		
		
		if($this->isModified()) {
			$logAction = $this->isNew() ? self::LOG_ACTION_CREATE : self::LOG_ACTION_UPDATE;
			GO()->log($logAction, $this->name.': '.implode(',', $this->getModified()), $this);		

			$notification = new \GO\Core\Notifications\Model\Notification();
			$notification->iconBlobId = $this->photoBlobId;
			$notification->type = $logAction;		
			$notification->data = $this->toArray('id,name');
			$notification->record = $this;
			
			if(!$notification->save()) {
				return false;
			}
		}

		return true;
		

	}	
	
	protected function internalDelete($hard) {
		
		GO()->log(self::LOG_ACTION_DELETE, $this->name, $this);
		
		$this->freeBlob($this->photoBlobId);
		
		return parent::internalDelete($hard);
	}
	
	protected static function internalGetPermissions() {
		return new ContactPermissions();
	}
	
	
	public function getLanguage() {
		$lang = $this->language;
		
		if(!isset($lang)) {
			return GO()->getSettings()->defaultLanguage;
		}
		
		return $lang;
	}
	
	public function setLanguage() {
		return $this->language;
	}

}
