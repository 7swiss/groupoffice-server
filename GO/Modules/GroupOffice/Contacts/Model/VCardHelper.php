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
	static public function toVCard(Contact $contact) {

		$vcard = new VObject\Component\VCard([
			'PRODID' => self::PRODID,
			'VERSION' => '3.0', // @todo: use 4.0 when URI Photo base64 is working properly
			'UID' => $contact->id.'@nouuid',
			'N' => [$contact->lastName, $contact->firstName, $contact->middleName, $contact->prefixes, $contact->suffixes],
			'FN' => $contact->name,
			'REV' => $contact->modifiedAt->getTimestamp(),
		]);
		foreach($contact->emailAddresses as $emailAddr) {
			$vcard->add('EMAIL', $emailAddr->email, ['TYPE'=>[$emailAddr->type]]);
		}
		foreach($contact->phoneNumbers as $phoneNb) {
			$vcard->add('TEL', $phoneNb->number, ['TYPE'=>[$emailAddr->type]]);
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
				['TYPE'=>[$address->type]]
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

		!isset($contact->function) ?: $vcard->TITLE = $contact->function; // @todo implement in ContactOrganization
		!isset($contact->url) ?: $vcard->URL = $contact->url; // @todo: decide if we are going to implement this
		empty($contact->notes) ?: $vcard->NOTE = $contact->notes;
		empty($contact->gender) ?: $vcard->GENDER = $contact->gender;

		if(!empty($contact->photoBlobId)) {
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
	static public function fromVCard(VObject\Splitter\VCard $vcards) {

		$contacts = [];
		while($vcard = $vcards->getNext()) {
			$contact = new Contact();
			//$contact->uuid = (string)$vcard->UID; todo
			
			$n = $vcard->N->getParts();
			empty($n[0]) ?: $contact->lastName = $n[0];
			empty($n[1]) ?: $contact->firstName = $n[1];
			empty($n[2]) ?: $contact->middleName = $n[2];
			empty($n[3]) ?: $contact->prefixes = $n[3];
			empty($n[4]) ?: $contact->suffixes = $n[4];
			$contact->name = empty((string)$vcard->FN) ? self::EMPTY_NAME : (string)$vcard->FN;

			empty($vcard->BDAY) ?: $contact->dates[] = ['date'=>$vcard->BDAY, 'type'=>'birthday'];
			empty($vcard->ANNIVERSARY) ?: $contact->dates[] = ['date'=>$vcard->ANNIVERSARY, 'type'=>'anniversary'];
			empty($vcard->NOTE) ?: $contact->notes = (string)$vcard->NOTE;

			//ORG ??
			if(!empty($vcard->TEL)) {
				foreach($vcard->TEL as $tel) {
					$contact->phoneNumbers[] = ['number'=>(string)$tel, 'type' => self::convertType($tel['TYPE'])];
				}
			}
			if(!empty($vcard->EMAIL)) {
				foreach($vcard->EMAIL as $email) {
					$contact->emailAddresses[] = ['email'=>(string)$email, 'type' => self::convertType($email['TYPE'])];
				}
			}
			if(!empty($vcard->ADR)) {
				foreach($vcard->ADR as $adr) {
					$a = $adr->getParts();
					$addr = ['type' => self::convertType($adr['TYPE'])];
					empty($a[2]) ?: $addr['street'] = $a[2];
					empty($a[3]) ?: $addr['city'] = $a[3];
					empty($a[4]) ?: $addr['state'] = $a[4];
					empty($a[5]) ?: $addr['zipCode'] = $a[5];
					empty($a[6]) ?: $addr['country'] = $a[6];
					$contact->addresses[] = $addr;
				}
			}
			if(!empty($vcard->PHOTO)) {
				$blob = Blob::fromString($vcard->PHOTO->getValue());
				if($blob->save()) {
					$contact->photoBlobId = $blob->blobId;
				}
			}
			$contacts[] = $contact;
		}
		return $contacts;
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