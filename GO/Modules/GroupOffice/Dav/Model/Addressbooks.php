<?php

//TODO

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\CardDAV\Backend\AbstractBackend,
	Sabre\DAV\Exception,
	IFW\App,
	IFW\Orm\Query,
	GO\Core\Users\Model\User,
	GO\Modules\GroupOffice\Contacts\Model\Contact,
	GO\Modules\GroupOffice\Contacts\Model\Company;

class Addressbooks extends AbstractBackend {

	/**
	 * Get the user model by principal URI
	 * 
	 * @param string $principalUri
	 * @return \GO\Base\Model\User 
	 */
	private function _getUser($principalUri) {
		return User::find(['username' => basename($principalUri)])->single();
	}
	
//	private function getUri($user){
//		return preg_replace('/[^\w-]*/', '', (strtolower(str_replace(' ', '-', $user->username)))).'-'.$user->id;
//	}

	/**
	 * Return an array with DAV property for a fake user addressbook
	 * @param string $principalUri
	 * @return array
	 */
	private function _buildBook($principalUri) {
		
		$query = (new Query())
			->select('max(modifiedAt) as modifiedAtTime, count(*) as count');

		$contact = Contact::find($query)->single();

		$ctag = time(); // $contact->count . ':' . $contact->modifiedAtTime;

		$user = $this->_getUser($principalUri);
		
		return array(
			'id' => $user->id,
			'uri' => "all", 
			'principaluri' => $principalUri,
//			'{DAV:}displayname' => $user->username,
			'{http://calendarserver.org/ns/}getctag' => $ctag,
			'{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}supported-address-data' => new \Sabre\CardDAV\Property\SupportedAddressData(),
			'{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' => 'User addressbook'
		);
	}

	private $_books;

	/**
	 * Returns the list of addressbooks for a specific user.
	 *
	 * Every addressbook should have the following properties:
	 *   id - an arbitrary unique id
	 *   uri - the 'basename' part of the url
	 *   principaluri - Same as the passed parameter
	 *
	 * Any additional clark-notation property may be passed besides this. Some
	 * common ones are :
	 *   {DAV:}displayname
	 *   {urn:ietf:params:xml:ns:carddav}addressbook-description
	 *   {http://calendarserver.org/ns/}getctag
	 *
	 * @param string $principalUri
	 * @return array
	 */
	public function getAddressBooksForUser($principalUri) {
		GO()->debug('a:getAddressbooksForUser(' . $principalUri . ')');

		if (!isset($this->_books[$principalUri])) {

			$this->_books[$principalUri] = array();
			$this->_books[$principalUri][] = $this->_buildBook($principalUri);
		}
		return $this->_books[$principalUri];
	}

	/**
	 * Updates an addressbook's properties
	 *
	 * See Sabre\DAV_IProperties for a description of the mutations array, as
	 * well as the return value.
	 *
	 * @param mixed $addressbookId
	 * @param array $mutations
	 * @see Sabre\DAV_IProperties::updateProperties
	 * @return bool|array
	 */
	public function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch) {
		throw new Exception\Forbidden();
	}

	/**
	 * Creates a new address book
	 *
	 * @param string $principalUri
	 * @param string $url Just the 'basename' of the url.
	 * @param array $properties
	 * @return void
	 */
	public function createAddressBook($principalUri, $url, array $properties) {
		throw new Exception\Forbidden();
	}

	/**
	 * Deletes an entire addressbook and all its contents
	 *
	 * @param mixed $addressbookId
	 * @return void
	 */
	public function deleteAddressBook($addressbookId) {
		throw new Exception\Forbidden();
	}

	/**
	 * Returns all cards for a specific addressbook id.
	 *
	 * This method should return the following properties for each card:
	 *   * carddata - raw vcard data
	 *   * uri - Some unique url
	 *   * lastmodified - A unix timestamp
	 *
	 * It's recommended to also return the following properties:
	 *   * etag - A unique etag. This must change every time the card changes.
	 *   * size - The size of the card in bytes.
	 *
	 * If these last two properties are provided, less time will be spent
	 * calculating them. If they are specified, you can also ommit carddata.
	 * This may speed up certain requests, especially with large cards.
	 *
	 * @param mixed $addressbookId
	 * @return array
	 */
	public function getCards($addressbookId) {
		GO()->debug('a:getCards(' . $addressbookId . ')');
		
		//Find all contact the user has read permissions to
		$contacts = Contact::find(\GO\Modules\GroupOffice\Contacts\Model\ContactPermissions::query());
		

		GO()->debug("Found " . $contacts->getRowCount() . " contacts");

		$cards = array();
		foreach($contacts as $contact) {
			$card = Card::findByPk($contact->id);

			// Saved UUID to contact model in GO6 here

			if (!$card) {
				$card = Card::fromContact($contact);
				if(!$card->save()) {
					throw new Exception\NotImplemented(var_export($card->getValidationErrors(), true));
				}
			}

			$cards[] = [
				'id' => $contact->id,
				'uri' => $card->uri,
				'carddata' => $card->data,
				'lastmodified' => $contact->modifiedAt->format('Ymd H:i:s'),
				'etag' => '"' . $contact->modifiedAt->format('Ymd H:i:s') . '"'
			];
		}
		return $cards;
	}

	/**
	 * Returns a specfic card.
	 *
	 * The same set of properties must be returned as with getCards. The only
	 * exception is that 'carddata' is absolutely required.
	 *
	 * @param mixed $addressbookId
	 * @param string $cardUri
	 * @return array
	 */
	public function getCard($addressbookId, $cardUri) {
		
		GO()->debug('a:getCard(' . $addressbookId . ',' . $cardUri . ')');
		
		$card = Card::find(['uri' => $cardUri])->single(); // GO6 checked for addr book id
		
		if (!empty($card)) {
			$contact = Contact::findByPk($card->id);

			if($contact->modifiedAt > $card->modifiedAt || empty($card->data)) {
				GO()->debug('Creating new card data.');
				$card->modifiedAt = $contact->modifiedAt;
				$card->data = $card->toVCard($contact)->serialize();
				$card->save();
			}
			// GO()->debug($data);

			$object = array(
				'id' => $card->id,
				'uri' => $card->uri,
				'carddata' => $card->data,
				'lastmodified' => $card->modifiedAt->format('Ymd H:i:s'),
				'etag' => '"' . $card->modifiedAt->format('Ymd H:i:s') . '"'
			);
			return $object;
		}
		
		throw new Exception\NotFound('File not found');
		
	}

	/**
	 * Creates a new card.
	 *
	 * The addressbook id will be passed as the first argument. This is the
	 * same id as it is returned from the getAddressbooksForUser method.
	 *
	 * The cardUri is a base uri, and doesn't include the full path. The
	 * cardData argument is the vcard body, and is passed as a string.
	 *
	 * @param mixed $addressbookId
	 * @param string $cardUri
	 * @param string $cardData VCard data string.
	 */
	public function createCard($addressbookId, $cardUri, $cardData) {
		GO()->debug('a:createCard(' . $addressbookId . ',' . $cardUri . ',[data])');

		$card = new Card($cardData);
		$card->uri = $cardUri;
		$card->uid = basename($cardUri, Card::EXTENSION);
		$contact = $card->toContact();
		
		if($contact->save()) {
			$card->id = $contact->id;
			return $card->save();
		}
		return false;
	}

	/**
	 * Updates a card.
	 *
	 * The addressbook id will be passed as the first argument. This is the
	 * same id as it is returned from the getAddressbooksForUser method.
	 *
	 * The cardUri is a base uri, and doesn't include the full path. The
	 * cardData argument is the vcard body, and is passed as a string.
	 *
	 * It is possible to return an ETag from this method. This ETag should
	 * match that of the updated resource, and must be enclosed with double
	 * quotes (that is: the string itself must contain the actual quotes).
	 *
	 * You should only return the ETag if you store the carddata as-is. If a
	 * subsequent GET request on the same card does not have the same body,
	 * byte-by-byte and you did return an ETag here, clients tend to get
	 * confused.
	 *
	 * If you don't return an ETag, you can just return null.
	 *
	 * @param mixed $addressbookId
	 * @param string $cardUri
	 * @param string $cardData
	 * @param string|null
	 */
	public function updateCard($addressbookId, $cardUri, $cardData) {
		GO()->debug('a:updateCard(' . $addressbookId . ',' . $cardUri . ',[data])');

		$card = Card::find(['uri' => $cardUri])->single(); // GO6 checked for addr book id
		$contact = Contact::findByPk($card->id);
		$card->setText($cardData);
		$card->save();
		
		if($card->toContact($contact)->save()) {
			GO()->debug ('Contact saved!');
		}


		return null;
	}

	/**
	 * Deletes a card
	 * 
	 * This leave the Card model behind in the database
	 * Was commented out in GO6 without reason
	 *
	 * @param mixed $addressbookId
	 * @param string $cardUri
	 * @return bool
	 */
	public function deleteCard($addressbookId, $cardUri) {
		GO()->debug('a:deleteCard(' . $addressbookId . ',' . $cardUri . ')');
		$card = Card::find(['uri' => $cardUri])->single();
		$contact = Contact::findByPk($card->id);
		return $contact->delete();
//		$card->delete();
	}

}