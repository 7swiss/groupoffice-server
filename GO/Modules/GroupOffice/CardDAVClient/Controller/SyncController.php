<?php
namespace GO\Modules\GroupOffice\CardDAVClient\Controller;

use GO\Core\Controller;
use Sabre\DAV\Client;
use Sabre\HTTP\ResponseMock;
use Sabre\HTTP\Sapi;

class SyncController extends Controller {
	
	protected function checkXSRF() {
		return true;
	}

	protected function actionTest() {


		$client = new \IFW\Dav\CardDAV('https://intermesh.group-office.com');
		$client->setAuth('test', 'T3stusr1!');
//		
		$response = $client->propFind("/carddav/addressbooks/test/test-man-1287", ['{DAV:}displayname','{cs:}getctag'], 0);
		
//				var_dump($response);
				
				
	$response = $client->report("/carddav/addressbooks/test/test-man-1287", ['{DAV:}getetag','{card:}address-data'], 1);
	
	echo "READER\n\n\n\n";
	
	$reader = new \XMLReader();
	$reader->XML($response['response']);
	
	while($reader->read()) {
		if("d:response" === $reader->name && $reader->nodeType == \XMLReader::ELEMENT) {
			 $card = $reader->readOuterXml();
			 
//			 var_dump($card);
			 
				$xml = simplexml_load_string($card);
				
				$davProps = $xml->children('DAV:');
				
			$response->propstat->prop->{card-data};
				
				var_dump($davProps->propstat->prop->getetag);
//				var_dump($xml->getNamespaces(true));
				
				$cardProps = $davProps->propstat->prop->children('urn:ietf:params:xml:ns:carddav');
				
				var_dump($cardProps->{"address-data"});
				
				$vCard = \Sabre\VObject\Reader::read($cardProps->{"address-data"});
				
				
//				foreach($xml->{"DAV:propstat"}->children('DAV:') as $child) {
//					echo $child->getName();
//				};//->{"d:prop"}->{"d:getetag"}."<br>";
		}
	}
//				var_dump($response);
//		$response = $client->getVcard('379f61c2-0691-59d4-b2e5-3ac41bf7a38e-15568');
				
//				var_dump($response);
				
//				$client->
      
	}
}

