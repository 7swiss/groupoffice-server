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
class Card extends Record{
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

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
	public $uid;

	const EXTENSION = '.vcf';
	
	protected $vobject;
	
	/**
	 * 
	 * @param string $data VCard text
	 */
	public function __construct($data = null) {
		if($data !== null) {
			$this->setText($data);
		}
		parent::__construct();
	}

	public function setText($data) {
		$this->data = $data;
		$this->vobject = Reader::read($data, Reader::OPTION_FORGIVING);
	}

	protected function getUuid() {
		if(empty($this->uid))
			$this->uid = \IFW\Util\UUID::create('contact', $this->id);
		return $this->uid;
	}
	
	public static function fromContact(Contact $contact) {
		$card = new self();
		$card->modifiedAt = $contact->modifiedAt;
		$card->id = $contact->id;
		$card->data = $card->toVCard($contact)->serialize();
		$card->uri = $card->getUuid() . '-' . $contact->id.self::EXTENSION;
		
		return $card;
	}
	
	public function toContact($contact = null) {
		if($contact === null) {
			$contact = new Contact();
		}
		
		$tel = [];
		$adr = [];
		$email = [];
		$dates = [];
		foreach ($this->vobject->children as $prop) {
			switch ($prop->name) {
				case 'N':
					$map = ['lastName', 'firstName', 'middleName', 'suffixes', 'prefixes'];
					$parts = explode(';',$prop->getValue());
					foreach($parts as $k => $attr) {
						$contact->{$map[$k]} = $attr;
					}
					break;
				case 'TEL':
					if(!$prop->getValue())
						break;
					$tel[] = ['type'=> strtolower((string)$prop['TYPE']), 'number'=>$prop->getValue()];
					break;
				case 'ADR':
					$parts = explode(';',$prop->getValue());
					$adr[] = [
						'type'=>(string)$prop['TYPE'],
						'street'=>$parts[2], 
						'city'=>$parts[3], 
						'state'=>$parts[4], 
						'zipCode'=>$parts[5], 
						'country'=>$parts[6]
					];
					break;
				case 'EMAIL':
					if($prop->getValue())
						$email[]=['type'=> strtolower((string)$prop['TYPE']), 'email'=>$prop->getValue()];
					break;
				case 'BDAY':
					if($prop->getValue()) {
						$dates[] = ['type' => 'birthday', 'date' => $prop->getValue()];
					}
					break;				
				case 'NOTE':
					$contact->notes = $prop->getValue();
					break;
				case 'VERSION':
				case 'LAST-MODIFIED':
					break;
			}
		}
		
//		$mAddresses = $contact->addresses->all();
//		$contact->addresses = $this->_mergeRel($mAddresses, $adr);
//		
		$mDates = $contact->dates->all();
		$contact->dates = $this->_mergeRel($mDates, $dates);
		
		$mEmails = $contact->emailAddresses->all();
		$contact->emailAddresses = $this->_mergeRel($mEmails, $email);
		
		$mPhoneNumbers = $contact->phoneNumbers->all();
		$contact->phoneNumbers = $this->_mergeRel($mPhoneNumbers, $tel);
		
		//TODO: Organisation + Photo
		
		return $contact;
	}
	
	private function _mergeRel($rel, $data) {
		$k=0;
		foreach($rel as $k => $v) {
			if(isset($data[$k])) {
				$v->setValues($data[$k]);
			} else {
				$v->markDeleted = true;
			}
			$rel[$k] = $v;
		}
		while(isset($data[++$k])) {
			$rel[$k] = $data[$k];
		}
		return $rel;
	}
	
	/**
	 * Build a VCard Object from a Contact model
	 * @param Contact $contact
	 * @return \Sabre\VObject\Component\VCard
	 */
	public function toVCard(Contact $contact) {
		
		$vcard = new \Sabre\VObject\Component\VCard();
		$c = GO()->getConfig(); // Version 
		$vcard->prodid = '-//Intermesh//NONSGML Group-Office '.$c::VERSION.'//EN';		

		
		$vcard->uid = $this->getUuid();
		$vcard->add('N',[
			$contact->lastName,
			$contact->firstName,
			$contact->middleName,
			$contact->prefixes,
			$contact->suffixes
		]);
		$vcard->add('FN',$contact->name);
		
		//Doesn't excist
		if (!empty($contact->function))
			$vcard->add('TITLE',$contact->function);
		
		foreach($contact->emailAddresses as $email) {
			$vcard->add('email',$email->email, ['type' => explode(',',strtoupper($email->type)) ]);
		}
		
		foreach($contact->phoneNumbers as $phonenb) {
			$vcard->add('TEL',$phonenb->number, ['type' =>  explode(',',strtoupper($phonenb->type)) ]);
		}
		
		foreach($contact->dates as $date) {
			if($date->type == 'birthday')
				$vcard->add('BDAY',$date->date);
		}
		
		foreach($contact->addresses as $addr) {
			$vcard->add('ADR', [
				'',
				'',
				$addr->street,
				$addr->city,
				$addr->state,
				$addr->zipCode,
				$addr->country
			],['type'=>explode(',',strtoupper($addr->type))]);
		}

		
//		if (!empty($contact->company)) {
//			
//			$vcard->add('ORG',array($contact->company->name,$contact->department,$contact->company->name2));
//			$vcard->add('ADR',array('','',$contact->company->address.' '.$contact->company->address_no,$contact->company->city,$contact->company->state,$contact->company->zip,$contact->company->country),array('type'=>'WORK'));
//			$vcard->add('ADR',array('','',$contact->company->post_address.' '.$contact->company->post_address_no,
//			$contact->company->post_city,$contact->company->post_state,$contact->company->post_zip,$contact->company->post_country),array('type'=>'WORK'));
//			
//		}
		
		if(!empty($contact->notes)){
			$vcard->note = $contact->notes;
		}
		
		$vcard->rev = $contact->modifiedAt->format("Y-m-d\TH:m:s\Z");
		
		
		if($contact->getPhoto()->hasFile()){
			$vcard->add('photo', $contact->photoFile()->getContents(),array('type'=>'JPEG','encoding'=>'b'));	
		}  
		
		return $vcard;
	}
}


				