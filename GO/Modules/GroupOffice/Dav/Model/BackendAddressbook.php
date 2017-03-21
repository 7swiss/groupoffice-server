<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\CardDAV;
use Sabre\DAV;
use Sabre\CardDAV\Backend\AbstractBackend;
use Sabre\CardDAV\Backend\SyncSupport;

use GO;
use GO\Core\Users\Model\User;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Model\VCardHelper;

/**
 * Things todo:
 * - implement getChangesForAddressBook()
 * - cache Cards
 * - create UIDs
 * - multiple addressbooks?
 */
class BackendAddressbook extends AbstractBackend implements SyncSupport {

	private function userByUri($uri) {
		return User::find(['username' => basename($uri)])->single();
	}
	
	/**
	 * Returns the list of addressbooks for a specific user.
	 *
	 * @param string $principalUri
	 * @return array
	 */
	function getAddressBooksForUser($principalUri) {

		$sum = GO()->getDbConnection()->query('SELECT max(modifiedAt) as highestModTime FROM contacts_contact')->fetch(\PDO::FETCH_ASSOC);// deleted have new modifiedAt a well
		//$sum = Contact::find((new \IFW\Orm\Query)->select('max(modifiedAt) as highestModTime'))->single();
		$ctag = $sum['highestModTime'];

		$addressBooks = [
			 [
				'id' => 1,
				'uri' => 'contacts',
				'principaluri' => $principalUri,
				'{DAV:}displayname' => 'All Contacts',
				'{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' => 'Only one addressbook that contains all accessable contacts',
				'{http://calendarserver.org/ns/}getctag' => $ctag,
				'{http://sabredav.org/ns}sync-token' => $ctag ? $ctag : '0', // should increase on every change
			]
		];

		return $addressBooks;
	}

	function updateAddressBook($addressBookId, DAV\PropPatch $propPatch) {
		throw new DAV\Exception\Forbidden();
	}

	function createAddressBook($principalUri, $url, array $properties) {
		throw new DAV\Exception\Forbidden();
	}

	function deleteAddressBook($addressBookId) {
		throw new DAV\Exception\Forbidden();
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
	function getCards($addressbookId) {
		$contacts = Contact::find()->all(); // todo by address bppl

//		$stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, size FROM ' . $this->cardsTableName . ' WHERE addressbookid = ?');
//		$stmt->execute([$addressbookId]);

		$result = [];
		foreach($contacts as $contact) {
			/* @var $contact Contact */
			$result[] = [
				'id' => $contact->id,
				'uri' => $contact->name.'-'.$contact->id,
				'lastmodified' => $contact->modifiedAt->getTimestamp(),
				'etag' => $contact->etag
				//size ??
			];
		}
		return $result;
	}

	/**
	 * Returns a specific card.
	 *
	 * The same set of properties must be returned as with getCards. The only
	 * exception is that 'carddata' is absolutely required.
	 *
	 * If the card does not exist, you must return false.
	 *
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @return array
	 */
	function getCard($addressBookId, $cardUri) {
		list($name, $id) = explode('-', $cardUri);
		$contact = Contact::findByPk($id);

		if(!$contact)
			return false;

		return [
			 'id' => $contact->id,
			 'carddata' => VCardHelper::toVCard($contact)->serialize(),
			 'uri' => $contact->name.'-'.$contact->id,
			 'etag' => $contact->etag,
			 'lastmodified' => $contact->modifiedAt->getTimestamp(),
			 //size?
		];
	}

	/**
	 * @param mixed $addressBookId
	 * @param array $uris
	 * @return array
	 */
	function getMultipleCards($addressBookId, array $uris) {

		$result = [];
		foreach($uris as $uri) {
			$result[] = $this->getCard($addressBookId, $uri);
		}
		return $result;
	}

	/**
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @param string $cardData
	 * @return string|null
	 */
	function createCard($addressBookId, $cardUri, $cardData) {

		//list($name, $id) = explode('-', $cardUri); // ignore for now

		$contact = VCardHelper::read($cardData);
		$contact->save();
		return $contact->etag;

		//Todo: save cache, strlen data, etag = md5 data, uri
	}

	/**
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @param string $cardData
	 * @return string|null
	 */
	function updateCard($addressBookId, $cardUri, $cardData) {
		list($name, $id) = explode('-', $cardUri);
		$contact = Contact::findByPk($id);
		$contact = VCardHelper::read($cardData);
		//todo merge
		//save

		return '"' . $contact->getETag() . '"';
	}

	/**
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @return bool
	 */
	function deleteCard($addressBookId, $cardUri) {
		list($name, $id) = explode('-', $cardUri);
		$contact = Contact::findByPk($id);
		return $contact->delete();
	}

	/**
	 * The getChanges method returns all the changes that have happened, since
	 * the specified syncToken in the specified address book.
	 *
	 * This function should return an array, such as the following:
	 *
	 * [
	 *   'syncToken' => 'The current synctoken',
	 *   'added'   => [
	 *      'new.txt',
	 *   ],
	 *   'modified'   => [
	 *      'updated.txt',
	 *   ],
	 *   'deleted' => [
	 *      'foo.php.bak',
	 *      'old.txt'
	 *   ]
	 * ];
	 *
	 * The returned syncToken property should reflect the *current* syncToken
	 * of the addressbook, as reported in the {http://sabredav.org/ns}sync-token
	 * property. This is needed here too, to ensure the operation is atomic.
	 *
	 * If the $syncToken argument is specified as null, this is an initial
	 * sync, and all members should be reported.
	 *
	 * The modified property is an array of nodenames that have changed since
	 * the last token.
	 *
	 * The deleted property is an array with nodenames, that have been deleted
	 * from collection.
	 *
	 * The $syncLevel argument is basically the 'depth' of the report. If it's
	 * 1, you only have to report changes that happened only directly in
	 * immediate descendants. If it's 2, it should also include changes from
	 * the nodes below the child collections. (grandchildren)
	 *
	 * The $limit argument allows a client to specify how many results should
	 * be returned at most. If the limit is not specified, it should be treated
	 * as infinite.
	 *
	 * If the limit (infinite or not) is higher than you're willing to return,
	 * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
	 *
	 * If the syncToken is expired (due to data cleanup) or unknown, you must
	 * return null.
	 *
	 * The limit is 'suggestive'. You are free to ignore it.
	 *
	 * @param string $addressBookId
	 * @param string $syncToken
	 * @param int $syncLevel
	 * @param int $limit
	 * @return array
	 */
	function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null) {

		// Current synctoken
		$sum = Contact::find((new \IFW\Orm\Query)->select('max(mtime) as mtime'))->single(); // deleted have new mtime to
		$currentToken = $sum->mtime;

		if (is_null($currentToken))
			return null;

		$result = [
			 'syncToken' => $currentToken,
			 'added' => [],
			 'modified' => [],
			 'deleted' => [],
		];

		if ($syncToken) {

			$query = "SELECT uri, operation FROM " . $this->addressBookChangesTableName . " WHERE synctoken >= ? AND synctoken < ? AND addressbookid = ? ORDER BY synctoken";
			if ($limit > 0)
				$query .= " LIMIT " . (int) $limit;

			// Fetching all changes
			$stmt = $this->pdo->prepare($query);
			$stmt->execute([$syncToken, $currentToken, $addressBookId]);

			$changes = [];

			// This loop ensures that any duplicates are overwritten, only the
			// last change on a node is relevant.
			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

				$changes[$row['uri']] = $row['operation'];
			}

			foreach ($changes as $uri => $operation) {

				switch ($operation) {
					case 1:
						$result['added'][] = $uri;
						break;
					case 2:
						$result['modified'][] = $uri;
						break;
					case 3:
						$result['deleted'][] = $uri;
						break;
				}
			}
		} else {
			// No synctoken supplied, this is the initial sync.
			$result['added'] = GO()->getDbConnection()->query('SELECT CONCAT(`name`, "-", `id`) as uri FROM contacts_contact')->fetchAll(\PDO::FETCH_ASSOC);
		}
		return $result;
	}

}
