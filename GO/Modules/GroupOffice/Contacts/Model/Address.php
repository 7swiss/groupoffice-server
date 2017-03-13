<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Record;


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
class Address extends Record {
	
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
	public $street = '';

	/**
	 * 
	 * @var string
	 */							
	public $zipCode = '';

	/**
	 * 
	 * @var string
	 */							
	public $city = '';

	/**
	 * 
	 * @var string
	 */							
	public $state = '';

	/**
	 * 
	 * @var string
	 */							
	public $country;

	const TYPE_POST = 'post';
	
	const TYPE_INVOICE = 'invoice';
	
	public static function internalGetPermissions() {
		return new ViaRelation('contact');
	}
	
	public static $defaultCountry = 'NL';
	
	public static function defineRelations(){
		self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	}
	
	protected function init() {		
		parent::init();
		
		if($this->isNew()) {
			$this->country = self::$defaultCountry;
		}
	}
	
	public function getFormatted(){
		$formatted = $this->street."\n".
				$this->zipCode." ".$this->city."\n".
				$this->state."\n".
				$this->country;
		
		//remove double new lines
		return preg_replace("/[\n]+/","\n", $formatted);
	}
	
}