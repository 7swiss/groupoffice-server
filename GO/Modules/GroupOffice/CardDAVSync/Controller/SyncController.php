<?php
namespace GO\Modules\GroupOffice\CardDAVSync\Controller;

use GO\Core\Controller;
use Sabre\DAV\Client;
use Sabre\HTTP\ResponseMock;
use Sabre\HTTP\Sapi;

class SyncController extends Controller {
	public function __construct(\IFW\Web\Router $router) {
		
//		throw new \Exception('test');
		parent::__construct($router);
	}
	protected function actionTest() {

      
        // using the client for parsing
        $client = new Client([
						'baseUri'=>'https://intermesh.group-office.com/',
						'userName' => 'test',
						'password' => 'T3stusr1!',
						'authType' => Client::AUTH_DIGEST
						]);
				
//				$response = $client->request('GET', '/caldav/calendars/test/test-man-1646'); 
//				var_dump($response);
				
				$response = $client->propFind('/caldav/calendars/test/test-man-1646', ['{DAV:}displayname', '{cs:}ctag']);
				
				var_dump($response);
				
//				$client->
      
	}
}

