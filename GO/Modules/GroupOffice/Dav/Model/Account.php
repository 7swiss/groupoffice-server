<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Exception;
use GO\Core\Accounts\Model\AccountAdaptorRecord;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use IFW\Dav\Client;
use IFW\Orm\Query;
use IFW\Util\Crypt;
use Sabre\VObject\UUIDUtil;

/**
 */
class Account extends AccountAdaptorRecord {

	/**
	 * 
	 * @var int
	 */
	public $id;
	public $url;
	public $username;
	protected $password;	
	protected $ctag;

	public function setPassword($password) {
		$crypt = new Crypt();
		$this->password = $crypt->encrypt($password);
	}

	private function getDescryptedPassword() {
		$crypt = new Crypt();
		if (!empty($this->password) && !$crypt->isEncrypted($this->password)) {
			$this->password = $crypt->encrypt($this->password);
			$this->update();

			return $this->getDescryptedPassword();
		} else {
			return $crypt->decrypt($this->password);
		}
	}
	public static function getCapabilities() {
		return [Contact::class];
	}

	private $client;

	/**
	 * 
	 * @return Client
	 */
	public function connect() {
		if (!isset($this->client)) {
			$this->client = new Client($this->getHost());
			$this->client->setAuth($this->username, $this->getDescryptedPassword());
		}

		return $this->client;
	}

	private function syncRequired() {
		if (!isset($this->ctag)) {
			return true;
		}
		return $this->getRemoteCtag() != $this->ctag;
	}
	
	private function getHost() {
		return parse_url($this->url, PHP_URL_SCHEME).'://'.parse_url($this->url, PHP_URL_HOST);
	}
	
	private function getPath() {
//		var_dump(parse_url($this->url, PHP_URL_PATH));
//		exit();
		return parse_url($this->url, PHP_URL_PATH);
	}

	private function getRemoteCtag() {

		$response = $this->connect()->propFind($this->getPath(), ['{DAV:}displayname', '{cs:}getctag'], 0);

		/**
		 * <d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav">
		  <d:response>
		  <d:href>/carddav/addressbooks/test/test-man-1287/</d:href>
		  <d:propstat>
		  <d:prop>
		  <d:displayname>Test Man</d:displayname>
		  <x1:getctag xmlns:x1="http://calendarserver.org/ns/">3:1371847188</x1:getctag>
		  </d:prop>
		  <d:status>HTTP/1.1 200 OK</d:status>
		  </d:propstat>
		  </d:response>
		  </d:multistatus>
		 * 
		 */
		$xml = $response->getBodyAsXml();
		return (string) $xml->xpath('d:response//cs:getctag')[0];
	}

	/**
	 * 
	 * @link http://sabre.io/dav/building-a-carddav-client/
	 * @return boolean
	 */
	public function sync() {

		if ($this->syncRequired()) {
			$etags = $this->getRemoteEtags();
			$this->serverUpdates($etags);
		}

		$this->clientUpdates();


		$this->ctag = $this->getRemoteCtag();
		return $this->save();
	}

	private function serverUpdates($etags) {
		$fetch = [];
		foreach ($etags as $uri => $etag) {
			$card = AccountCard::find(['accountId' => $this->id, 'uri' => $uri])->single();
			if (!$card || $card->etag != $etag) {
				$fetch[] = $uri;
			}
		}

		$response = $this->connect()->multiget($this->getPath(), ['d:getetag', 'card:address-data'], $fetch);

		foreach ($response->getMultiResponse() as $subResponse) {
			$uri = (string) $subResponse->xpath('//d:href')[0];
			$vcard = (string) $subResponse->xpath('//card:address-data')[0];

			$card = AccountCard::find(['accountId' => $this->id, 'uri' => $uri])->single();
			if (!$card) {
				$card = new AccountCard();
				$card->accountId = $this->id;
				$card->uri = $uri;
			}
			$card->etag = (string) $subResponse->xpath('//d:getetag')[0];
			$card->data = $vcard;
			$card->save();
		}


		//todo this will probably fail on large accounts
		$deletedCards = AccountCard::find(
										(new Query())
														->where(['accountId' => $this->id])
														->where(['!=', ['uri' => array_keys($etags)]])
		);

		foreach ($deletedCards as $deletedCard) {
			$deletedCard->delete();
		}
	}

	//TODO account moves
	private function clientUpdates() {
		$updatedCards = AccountCard::find(
										(new Query)
														->where(['accountId' => $this->id])
														->withDeleted()
														->joinRelation('contact', true)
														->where('contact.modifiedAt > t.modifiedAt')
		);

		foreach ($updatedCards as $card) {

			if ($card->contact->deleted) {

				$this->deleteContact($card);
			} else {
				$this->updateContact($card);
			}
		}
		
		$this->clientCreateContacts();
		
		
	}
	
	private function clientCreateContacts() {
		
		$cards = AccountCard::find(
							(new Query)
						->tableAlias('cards')
						->where('cards.contactId = t.id')
						);
		
		$contacts = Contact::find(
						(new Query)
						->where(['accountId' => $this->id])
						->where(['NOT EXISTS', $cards])
						);
		
		foreach($contacts as $contact) {
			
			$card = new AccountCard();
			$card->uri = $this->getPath() . UUIDUtil::getUUID().'.vcf';
			$card->account = $this;
			$card->contact = $contact;
			$card->updateFromContact();
		
			
			$response = $this->connect()->put($card->uri, $card->data);

			if ($response->status != 201) { //no content			
				throw new Exception("DAV server returned " . $response->status . " " . $response->body);
			}

			$card->etag = $response->headers['etag'];
			if (!$card->save()) {
				throw new Exception("Couldn't save card");
			}
			
			
		}
	}

	private function updateContact(AccountCard $card) {
		$card->updateFromContact();
		$response = $this->connect()->put($card->uri, $card->data, $card->etag);

		if ($response->status != 204) { //no content			
			throw new Exception("DAV server returned " . $response->status . " " . $response->body);
		}

		$card->etag = $response->headers['etag'];
		if (!$card->save()) {
			throw new Exception("Couldn't save card");
		}
	}

	private function deleteContact(AccountCard $card) {

		$response = $this->connect()->delete($card->uri, $card->etag);

		if ($response->status != 204) { //no content			
			throw new Exception("DAV server returned " . $response->status . " " . $response->body);
		}

		if (!$card->delete()) {
			throw new Exception("Couldn't save card");
		}
	}

	private function getRemoteEtags() {
		$client = $this->connect();

		//	$response = $client->report($this->getPath(), ['{DAV:}getetag','{card:}address-data'], 1);

		/**
		 * <d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav">
		  <d:response>
		  <d:href>/carddav/addressbooks/test/test-man-1287/53b4305e-c2c6-5acd-9085-5d1ee2b21350-12873</d:href>
		  <d:propstat>
		  <d:prop>
		  <d:getetag>&quot;20121002 10:12:44-12873&quot;</d:getetag>
		  </d:prop>
		  <d:status>HTTP/1.1 200 OK</d:status>
		  </d:propstat>
		  </d:response>
		  <d:response>
		  <d:href>/carddav/addressbooks/test/test-man-1287/6d680ed4-cd4f-5f6e-a4bb-de3d250d9dcc-15567</d:href>
		  <d:propstat>
		  <d:prop>
		  <d:getetag>&quot;20130621 20:30:18-15567&quot;</d:getetag>
		  </d:prop>
		  <d:status>HTTP/1.1 200 OK</d:status>
		  </d:propstat>
		  </d:response>
		  <d:response>
		  <d:href>/carddav/addressbooks/test/test-man-1287/379f61c2-0691-59d4-b2e5-3ac41bf7a38e-15568</d:href>
		  <d:propstat>
		  <d:prop>
		  <d:getetag>&quot;20130621 22:39:48-15568&quot;</d:getetag>
		  </d:prop>
		  <d:status>HTTP/1.1 200 OK</d:status>
		  </d:propstat>
		  </d:response>
		  </d:multistatus>

		 */
		$response = $client->report($this->getPath(), ['{DAV:}getetag'], 1);

		$etags = [];

		foreach ($response->getMultiResponse() as $subResponse) {
			$uri = (string) $subResponse->xpath('//d:href')[0];
			$etag = (string) $subResponse->xpath('//d:getetag')[0];
			$etags[$uri] = $etag;
		}

		return $etags;
	}

}
