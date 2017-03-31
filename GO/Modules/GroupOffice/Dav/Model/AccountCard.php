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
	 * Universal Unique Identifier @see getUuid()
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




//	public static function createFromContact(Contact $contact) {
//		$card = new self();
//		$card->modifiedAt = $contact->modifiedAt;
//		$card->contact = $contact;
//		$vobject = $card->toVCard();
//		$card->data = $vobject->serialize();
//		$card->uri = $vobject->uid . '.' . self::EXTENSION;
//		$this->modifiedAt = new \DateTime();
//		$card->etag = $this->modifiedAt->format('Ymd-Hi:s');
//
//		return $card;
//	}
	

	protected function internalSave() {	
		$this->toContact();		
		
//		var_dump($this->contact->toArray('lastName,firstName'));
//		exit();
		
		return parent::internalSave();
	}


	private function toContact() {
		if ($this->contact === null) {
			$vcard = $this->toVCard();
		
			$this->contact = \GO\Modules\GroupOffice\Contacts\Model\VCardHelper::fromVCard($vcard);
		}
		
		
		
		
	}

	private function mergeRel($rel, $data) {
		$k = 0;
		foreach ($rel as $k => $v) {
			if (isset($data[$k])) {
				$v->setValues($data[$k]);
			} else {
				$v->markDeleted = true;
			}
			$rel[$k] = $v;
		}
		while (isset($data[++$k])) {
			$rel[$k] = $data[$k];
		}
		return $rel;
	}

	/**
	 * Build a VCard Object from a Contact model
	
	 * @return \Sabre\VObject\Component\VCard
	 */
	public function toVCard() {
		
		if(isset($this->data)) {			
			$vcard = Reader::read($this->data, Reader::OPTION_FORGIVING);
		}else
		{
			$vcard = new \Sabre\VObject\Component\VCard();
		}
		$c = GO()->getConfig(); // Version 
		$vcard->prodid = '-//Intermesh//NONSGML Group-Office ' . $c::VERSION . '//EN';

		if(!isset($vcard->uid)) {
			$vcard->uid = \IFW\Util\UUID::create('contact', $this->id);
		}
		
		return $vcard;
		
		$vcard->add('N', [
				$this->contact->lastName,
				$this->contact->firstName,
				$this->contact->middleName,
				$this->contact->prefixes,
				$this->contact->suffixes
		]);
		$vcard->add('FN', $this->contact->name);

		//Doesn't excist
		if (!empty($this->contact->function))
			$vcard->add('TITLE', $this->contact->function);

		foreach ($this->contact->emailAddresses as $email) {
			$vcard->add('email', $email->email, ['type' => explode(',', strtoupper($email->type))]);
		}

		foreach ($this->contact->phoneNumbers as $phonenb) {
			$vcard->add('TEL', $phonenb->number, ['type' => explode(',', strtoupper($phonenb->type))]);
		}

		foreach ($this->contact->dates as $date) {
			if ($date->type == 'birthday')
				$vcard->add('BDAY', $date->date);
		}

		foreach ($this->contact->addresses as $addr) {
			$vcard->add('ADR', [
					'',
					'',
					$addr->street,
					$addr->city,
					$addr->state,
					$addr->zipCode,
					$addr->country
							], ['type' => explode(',', strtoupper($addr->type))]);
		}


//		if (!empty($contact->company)) {
//			
//			$vcard->add('ORG',array($contact->company->name,$contact->department,$contact->company->name2));
//			$vcard->add('ADR',array('','',$contact->company->address.' '.$contact->company->address_no,$contact->company->city,$contact->company->state,$contact->company->zip,$contact->company->country),array('type'=>'WORK'));
//			$vcard->add('ADR',array('','',$contact->company->post_address.' '.$contact->company->post_address_no,
//			$contact->company->post_city,$contact->company->post_state,$contact->company->post_zip,$contact->company->post_country),array('type'=>'WORK'));
//			
//		}

		if (!empty($this->contact->notes)) {
			$vcard->note = $this->contact->notes;
		}

//		$vcard->rev = $this->contact->modifiedAt->format("Y-m-d\TH:m:s\Z");


//		if ($this->contact->getPhoto()->hasFile()) {
//			$vcard->add('photo', $this->contact->photoFile()->getContents(), array('type' => 'JPEG', 'encoding' => 'b'));
//		}

		return $vcard;
	}

}
