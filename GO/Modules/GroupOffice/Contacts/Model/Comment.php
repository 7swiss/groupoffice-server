<?php

namespace GO\Modules\GroupOffice\Contacts\Model;

use GO\Core\Comments\Model\Comment as CoreComment;
use IFW\Auth\Permissions\ViaRelation;

/**
 * @property Contact $contact The contact this comment belongs to
 */
class Comment extends CoreComment {

	/**
	 *
	 * @var int 
	 */
	public $contactId;

	protected static function defineRelations() {
		parent::defineRelations();

		self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	}

	/**
	 * Permissions via contact
	 * 
	 * @return \GO\Modules\GroupOffice\Contacts\Model\ViaRelation
	 */
	protected static function internalGetPermissions() {
		return new ViaRelation('contact');
	}

}