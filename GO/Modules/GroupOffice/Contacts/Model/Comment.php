<?php

namespace GO\Modules\GroupOffice\Contacts\Model;

use GO\Core\Comments\Model\Comment as CoreComment;
use GO\Core\Notifications\Model\Notification;
use IFW\Auth\Permissions\ViaRelation;
use IFW\Util\StringUtil;

/**
 * @property Contact $contact The contact this comment belongs to
 */
class Comment extends \IFW\Orm\Record {
	
	/**
	 *
	 * @var int 
	 */
	public $commentId;

	/**
	 *
	 * @var int 
	 */
	public $contactId;

	protected static function defineRelations() {
		parent::defineRelations();

		self::hasOne('contact', Contact::class, ['contactId' => 'id']);
		
		self::hasOne('comment', CoreComment::class, ['commentId' => 'id'])->allowPermissionTypes([
				\IFW\Auth\Permissions\Model::PERMISSION_READ,
				\IFW\Auth\Permissions\Model::PERMISSION_CREATE,
				\IFW\Auth\Permissions\Model::PERMISSION_WRITE,
		]);
		
	}

	/**
	 * Permissions via contact
	 * 
	 * @return \GO\Modules\GroupOffice\Contacts\Model\ViaRelation
	 */
	protected static function internalGetPermissions() {
		return new ViaRelation('contact');
	}
	
	protected function internalSave() {
		
		if($this->isNew()) {
			
			$data = $this->contact->toArray('id,name');
			$data['excerpt'] = StringUtil::cutString(strip_tags($this->comment->content), 50);
			
			if(!Notification::create('comment', $data, $this->contact)){			
				return false;
			}
		}
		
		return parent::internalSave();
	}

}