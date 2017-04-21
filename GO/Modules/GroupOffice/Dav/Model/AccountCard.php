<?php

/**
 * Group-Office
 * 
 * Copyright Intermesh BV. 
 * This file is part of Group-Office. You should have received a copy of the
 * Group-Office license along with Group-Office. See the file /LICENSE.TXT
 *
 * If you have questions write an e-mail to info@intermesh.nl
 * 
 * @todo create mapping and make independent of DAV
 */

namespace GO\Modules\GroupOffice\Dav\Model;

use IFW\Orm\Record,
		IFW\App,
		GO\Modules\GroupOffice\Contacts\Model\Contact,
		Sabre\VObject\Reader;

/**
 * The Card model
 *
 */
class AccountCard extends Record {

	/**
	 * 
	 * @var int
	 */
	public $contactId;
	
	/**
	 * 
	 * @var int
	 */
	public $accountId;

	/**
	 * 
	 * @var \DateTime
	 */
	public $modifiedAt;

	/**
	 * 
	 * @var string
	 */
	public $data;

	/**
	 * 
	 * @var string
	 */
	public $uri;

	/**
	 * 
	 * @var string
	 */
	public $etag;

	const EXTENSION = 'vcf';

	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('contact');
	}
	
	protected static function defineRelations() {
		self::hasOne('contact', Contact::class, ['contactId' => 'id']);
	}


	protected function internalSave() {	
		
		if($this->isModified('data')) {			
			$vcard = Reader::read($this->data, Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
			$this->contact = \GO\Modules\GroupOffice\Contacts\Model\VCardHelper::fromVCard($vcard, $this->contact);		
			$this->contact->accountId = $this->accountId;
			
			//sync modifiedAt
			$this->contact->modifiedAt = $this->modifiedAt = new \DateTime();
		}
		
		return parent::internalSave();
	}
	
	
	public function updateFromContact() {
		if($this->contact && $this->contact->modifiedAt > $this->modifiedAt) {
			//update vcard
			$vcard = Reader::read($this->data, Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
			
			$vcard = \GO\Modules\GroupOffice\Contacts\Model\VCardHelper::toVCard($this->contact, $vcard);				
			
			$this->data = $vcard->serialize();
			$this->modifiedAt = $this->contact->modifiedAt;
//			$this->etag = '"'. $this->modifiedAt->format('Ymd Gis') . '"';
			//$this->save();
		}
	}
	

	
	protected function internalDelete($hard) {
		
		if(!$this->contact->delete()) {
			return false;
		}
		
		return parent::internalDelete($hard);
	}
}
