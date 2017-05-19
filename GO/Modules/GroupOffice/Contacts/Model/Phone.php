<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Record;

/**
 * The contact model
 *
 * @property Contact $contact
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Phone extends \IFW\Orm\PropertyRecord {	
	
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
	public $type;

	/**
	 * 
	 * @var string
	 */							
	public $number;

	const TYPE_WORK = 'work';
	
	const TYPE_HOME = 'home';
	
	const TYPE_MOBILE = 'cell';
	
	const TYPE_FAX = 'fax';
	
	public static function defineRelations() {
		self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation( 'contact');
	}
}