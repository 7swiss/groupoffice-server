<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

/**
 * The ContactGroup model
 *
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class ContactOrganization extends \IFW\Orm\Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $contactId;

	/**
	 * 
	 * @var int
	 */							
	public $organizationContactId;

	protected static function defineRelations() {
		self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('contact');
	}
}
