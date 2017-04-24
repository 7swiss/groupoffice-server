<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use GO\Modules\GroupOffice\Contacts\Model\Contact;
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
class Url extends \IFW\Orm\PropertyRecord {	
	
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $contactId;

	/**
	 * 
	 * @var string
	 */							
	protected $url;

	public static function defineRelations(){
		self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation('contact');
	}
	
	public function setUrl($url) {
		if(!strpos($url, '://')){
			$url = 'http://'.$url;
		}
		
		$this->url = $url;
	}
	
	public function getUrl() {
		return $this->url;
	}
}

