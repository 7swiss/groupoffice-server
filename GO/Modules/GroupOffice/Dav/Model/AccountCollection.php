<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use GO\Core\Orm\Record;

/**
 * The AccountCollections record
 * 
 * @property Account $account
 *
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AccountCollection extends Record {

	/**
	 * 
	 * @var int
	 */
	public $id;

	/**
	 * 
	 * @var int
	 */
	public $accountId;

	/**
	 * 
	 * @var int
	 */
	public $uri;

	/**
	 * 
	 * @var string
	 */
	public $ctag;

	protected static function defineRelations() {
		parent::defineRelations();

		self::hasOne('account', Account::class, ['accountId' => 'id']);
	}

	private function syncRequired() {
		if (!isset($this->ctag)) {
			return true;
		}



		return $this->getRemoteCtag() != $this->ctag;
	}

	
	private function getRemoteCtag() {

		$response = $this->account->getClient()->propFind($this->uri, ['{DAV:}displayname', '{cs:}getctag'], 0);

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
		foreach($etags as $uri => $etag) {
			$card = AccountCard::find(['accountId' => $this->accountId, 'uri' => $uri])->single();			
			if(!$card || $card->etag != $etag) {
				$fetch[] = $uri;
			}
		}
		
		$response = $this->account->getClient()->multiget($this->uri, ['d:getetag', 'card:address-data'], $fetch);
		
		foreach($response->getMultiResponse() as $subResponse) {
			$uri = (string) $subResponse->xpath('//d:href')[0];
			$vcard = (string) $subResponse->xpath('//card:address-data')[0];
			
			$card = AccountCard::find(['accountId' => $this->accountId, 'uri' => $uri])->single();
			if(!$card) {
				$card = new AccountCard();
				$card->accountId = $this->accountId;
				$card->uri = $uri;				
			}
			$card->etag = (string) $subResponse->xpath('//d:getetag')[0];
			$card->data = $vcard;
			$card->save();
		}	
		
		
		//todo this will probably fail on large accounts
		$deletedCards = AccountCard::find(
						(new \IFW\Orm\Query())
						->where(['accountId' => $this->accountId])
						->where(['!=', ['uri' => array_keys($etags)]])
						);
		
		foreach($deletedCards as $deletedCard) {
			$deletedCard->delete();
		}
	}
	
	private function clientUpdates() {
		$updatedCards = AccountCard::find(
						(new \IFW\Orm\Query)
						->where(['accountId' => $this->accountId])
						->withDeleted()
						->joinRelation('contact', true)
						->where('contact.modifiedAt > t.modifiedAt')
						);
		
		foreach($updatedCards as $card) {			

			if($card->contact->deleted) {
				
				$this->deleteContact($card);
				
			} else {
				$this->updateContact($card);
			}
		}
		exit();
		
	}
	
	private function updateContact(AccountCard $card) {
		$card->updateFromContact();
		$response = $this->account->getClient()->put($card->uri, $card->data, $card->etag);

		if($response->status != 204) { //no content			
			throw new \Exception("DAV server returned ".$response->status." ".$response->body);
		}
		
		$card->etag = $response->headers['etag'];
		if(!$card->save()) {
			throw new \Exception("Couldn't save card");
		}

	}
	
	private function deleteContact(AccountCard $card) {
		
		$response = $this->account->getClient()->delete($card->uri, $card->etag);

		if($response->status != 204) { //no content			
			throw new \Exception("DAV server returned ".$response->status." ".$response->body);
		}

		if(!$card->delete()) {
			throw new \Exception("Couldn't save card");
		}

	}
	
	private function getRemoteEtags() {
		$client = $this->account->getClient();

	//	$response = $client->report($this->uri, ['{DAV:}getetag','{card:}address-data'], 1);

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
		$response = $client->report($this->uri, ['{DAV:}getetag'], 1);
		
		$etags = [];

		foreach($response->getMultiResponse() as $subResponse) {			
				$uri = (string) $subResponse->xpath('//d:href')[0];
				$etag = (string) $subResponse->xpath('//d:getetag')[0];
				$etags[$uri] = $etag;			
		}
		
		return $etags;
		
	
	}
}