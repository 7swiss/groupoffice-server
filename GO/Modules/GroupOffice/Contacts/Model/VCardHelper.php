<?php
/**
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Contacts\Model;

use GO\Core\Blob\Model\Blob;
use IFW\Util\Image;
use Sabre\VObject;

class VCardHelper {

	const PRODID = '-//Intermesh//NONSGML Group-Office 7//EN';
	const EMPTY_NAME = '(no name)';

	/**
	 * Parse an Event object to a VObject
	 * @param Contact $contact
	 */
	static public function toVCard(Contact $contact, \Sabre\VObject\Component\VCard $vcard = null) {
		
		if(!isset($vcard)) {
				$vcard = new VObject\Component\VCard([
						"VERSION" => "3.0"
						]);
		}else
		{
			//remove all supported properties
			$vcard->remove('EMAIL');
			$vcard->remove('TEL');
			$vcard->remove('ADR');
			$vcard->remove('ORG');
			$vcard->remove('CATEGORIES');
		}
		
		$vcard->PRODID = self::PRODID;		
		$vcard->N = [$contact->lastName, $contact->firstName, $contact->middleName, $contact->prefixes, $contact->suffixes];
		$vcard->FN = $contact->name;
		$vcard->REV = $contact->modifiedAt->getTimestamp();		
		
		
		foreach($contact->emailAddresses as $emailAddr) {
			$vcard->add('EMAIL', $emailAddr->email, ['TYPE'=>[$emailAddr->type]]);
		}
		foreach($contact->phoneNumbers as $phoneNb) {
			$vcard->add('TEL', $phoneNb->number, ['TYPE'=>[$phoneNb->type]]);
		}
		foreach($contact->dates as $date) {
			$type = ($date->type === 'birthday') ? 'BDAY' : 'ANNIVERSARY';
			$vcard->add($type, $date->date);
		}
		foreach($contact->addresses as $address) {
			//ADR: [post-office-box, apartment, street, locality, region, postal, country]
			$vcard->add(
				'ADR',
				['','',$address->street, $address->city,$address->state,$address->zipCode,$address->country], // @todo country must be full name
				['TYPE' => [$address->type]]
			);
		}
		foreach($contact->organizations as $org) {
			$vcard->add('ORG',[$org->name]);
		}
		$categories = [];
		foreach($contact->tags as $tag) {
			$categories[] = $tag->name;
		}
		$vcard->CATEGORIES = $categories; // @todo: test

//		!isset($contact->function) ?: $vcard->TITLE = $contact->function; // @todo implement in ContactOrganization
//		!isset($contact->url) ?: $vcard->URL = $contact->url; // @todo: decide if we are going to implement this
		$vcard->NOTE = $contact->notes;
		$vcard->GENDER = $contact->gender;

		if(!empty($contact->photoBlobId) && file_exists($contact->photoBlob->getPath())) {
			$image = new Image($contact->photoBlob->getPath());
			$image->fitBox(120, 120);
			$vcard->add('PHOTO', $image->contents(), ['TYPE'=>$contact->photoBlob->getFormat(), 'ENCODING'=>'b']);
		}
		return $vcard;
	}

	/**
	 * Parse a VObject to an Contact object
	 * @param VObject\Component\VCard $vcard
	 * @return Contact[]
	 */
	static public function fromVCard($vcard, Contact $contact = null) {

			if(!isset($contact)) {
				$contact = new Contact();
			}
			
			$n = $vcard->N->getParts();
			empty($n[0]) ?: $contact->lastName = $n[0];
			empty($n[1]) ?: $contact->firstName = $n[1];
			empty($n[2]) ?: $contact->middleName = $n[2];
			empty($n[3]) ?: $contact->prefixes = $n[3];
			empty($n[4]) ?: $contact->suffixes = $n[4];
			$contact->name = empty((string) $vcard->FN) ? self::EMPTY_NAME : (string)$vcard->FN;

			empty($vcard->BDAY) ?: $contact->dates[] = ['date'=>$vcard->BDAY, 'type'=>'birthday'];
			empty($vcard->ANNIVERSARY) ?: $contact->dates[] = ['date'=>$vcard->ANNIVERSARY, 'type'=>'anniversary'];
			
			empty($vcard->NOTE) ?: $contact->notes = (string)$vcard->NOTE;

			self::mergeRelation($contact->phoneNumbers, $vcard->TEL, function($value) {
				 return ['number'=>(string) $value, 'type' => self::convertType($value['TYPE'])];
			});
			
			self::mergeRelation($contact->emailAddresses, $vcard->EMAIL, function($value) {				
				 return ['email' => (string) $value, 'type' => self::convertType($value['TYPE'])];					
			});
			
			//TODO CATEGORIES -> tags
			
			self::mergeRelation($contact->addresses, $vcard->ADR, function($value) {
				$a = $value->getParts();
					$addr = ['type' => self::convertType($value['TYPE'])];
					empty($a[2]) ?: $addr['street'] = $a[2];
					empty($a[3]) ?: $addr['city'] = $a[3];
					empty($a[4]) ?: $addr['state'] = $a[4];
					empty($a[5]) ?: $addr['zipCode'] = $a[5];
					empty($a[6]) ?: $addr['country'] = $a[6];
					return $addr;
			});
			
			self::mergeOrg($contact, $vcard);
			
			
			if(!empty($vcard->PHOTO)) {
				$blob = Blob::fromString($vcard->PHOTO->getValue());
				if($blob->save()) {
					$contact->photoBlobId = $blob->blobId;
				}
			}
		
		return $contact;
	}
	
	private static function mergeOrg(Contact $contact, $vcard) {
		if(empty($vcard->ORG)) {
			return;
		}
		
		foreach($vcard->ORG as $org) {
			$parts = $org->getParts();			
			$org = Contact::find(['name' => $parts[0], 'isOrganization' => true])->single();
			
			if(!$org) {
				$org = new Contact();
				$org->isOrganization = true;
				$org->name = $parts[0];
			}
			
			$contact->organizations[] = $org;			
		}
	}
	
	private static function mergeRelation(\IFW\Orm\RelationStore $store, $vcardProp, $fn) {
		
		$vcardCount = isset($vcardProp) ? count($vcardProp) : 0;
		$contactCount = count($store->all());
		//remove emails
		for($i = $vcardCount; $i < $contactCount; $i++) {
			$store[$i]->markDeleted = true;
		}
		
		if(isset($vcardProp)) {
			foreach($vcardProp as $index => $value) {	
				$store[$index] = call_user_func($fn, $value);
			}	
		}		
	
	}

	private static function convertType($vCardType) {
		return ucwords(str_replace(',', ', ', strtolower((string)$vCardType)));
	}


	/**
	 * Read the VObject string data and return an Event object
	 * If a Blob object is passed and the mimeType is text/calendar teh contact will be fetched
	 *
	 * @param string|Blob $data VObject data
	 * @return VObject\Document
	 */
	static public function read($data) {
		if($data instanceof Blob && $data->contentType === 'text/x-vcard') {
			$data = $data->contents();
		}
		return new VObject\Splitter\VCard(\IFW\Util\StringUtil::cleanUtf8($data), VObject\Reader::OPTION_FORGIVING + VObject\Reader::OPTION_IGNORE_INVALID_LINES);
	}

}