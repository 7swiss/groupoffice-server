<?php
namespace GO\Modules\GroupOffice\Dav\Controller;

use GO\Core\Controller;
use IFW\Dav\Client;
use Sabre\VObject\Reader;
use XMLReader;

class SyncController extends Controller {
	
	protected function checkXSRF() {
		return true;
	}

	protected function actionTest() {
		
		$account = \GO\Modules\GroupOffice\Dav\Model\Account::find(['username' => 'test'])->single();
		
		if(!$account) {
			$account = new \GO\Modules\GroupOffice\Dav\Model\Account();
			$account->url = 'https://intermesh.group-office.com';
			$account->username = 'test';
			$account->password = 'T3stusr1!';
			
			$collection = new \GO\Modules\GroupOffice\DAV\Model\AccountCollection();
			$collection->uri = "/carddav/addressbooks/test/test-man-1287";
			
			$account->collections[] = $collection;
			
			if(!$account->save()) {
			echo	$this->renderModel($account);
				exit();
			}
		}
		
		$account->sync();

//		
	
      
	}
}

