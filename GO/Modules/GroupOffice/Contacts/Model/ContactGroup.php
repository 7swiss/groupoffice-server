<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use GO\Core\Auth\Permissions\Model\GroupAccess;

/**
 * The ContactGroup model
 *
 * @property boolean $read 
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ContactGroup extends GroupAccess {

	/**
	 * 
	 * @var int
	 */
	public $contactId;


	protected static function groupsFor() {
		return self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	}
}
