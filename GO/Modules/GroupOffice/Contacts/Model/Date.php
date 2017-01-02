<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Record;
use GO\Modules\GroupOffice\Contacts\Model\Contact;


/**
 * Contact address 
 * 
 * @property Contact $contact
 *
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Date extends Record {
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $sortOrder = 0;

	/**
	 * 
	 * @var int
	 */							
	public $contactId;

	/**
	 * 
	 * @var string
	 */							
	public $type = 'birthday';

	/**
	 * 
	 * @var \DateTime
	 */							
	public $date;

	public static function defineRelations(){
		self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation('contact');
	}
}