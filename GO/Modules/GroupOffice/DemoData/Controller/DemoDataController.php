<?php

namespace GO\Modules\GroupOffice\DemoData\Controller;

use IFW;
use GO\Core\Users\Model\User;
use GO\Core\Controller;


class DemoDataController extends Controller {
	
	protected function checkAccess() {
		return true;
		
	}
	public function actionCreate() {
		
		\GO()->getAuth()->sudo(function() {
		
		GO()->getConfig()->classConfig['IFW\Validate\ValidatePassword'] = ['enabled' => false];
		
		GO()->getDbConnection()->beginTransaction();
		
		$user = new User();
		$user->username = 'test';
		$user->password = 'test';
		if(!$user->save()) {
			echo "Failed!\n";
			var_dump($user->getValidationErrors());
			GO()->getDbConnection()->rollBack();
			return;
		}
		
		$contact = new \GO\Modules\GroupOffice\Contacts\Model\Contact();
		$contact->userId = $user->id;
		$contact->firstName = 'Test';
		$contact->lastName = 'User';
		$contact->emailAddresses = [['email'=>'test@intermesh.nl']];
		$contact->save();
		if(!$contact->save()) {
			echo "Failed!\n";
			var_dump($contact->getValidationErrors());
			GO()->getDbConnection()->rollBack();
			return;
		}
		
		
		$smtpAccount = new \GO\Modules\GroupOffice\Email\Model\SmtpAccount();
		$smtpAccount->fromEmail = 'test@intermesh.nl';
		$smtpAccount->fromName = 'Test user';
		$smtpAccount->hostname = 'smtp.group-office.com';
		$smtpAccount->port = 465;
		$smtpAccount->encryption = 'ssl';
		$smtpAccount->username = 'test@intermesh.nl';
		$smtpAccount->password =  'T3stusr1!';		
		$smtpAccount->ownerUserId = $user->id;
		if(!$smtpAccount->save()) {
			echo "Failed!\n";
			var_dump($smtpAccount->getValidationErrors());
			GO()->getDbConnection()->rollBack();
			return;
		}
		
		$imap = new \GO\Modules\GroupOffice\Email\Model\ImapAccount();
		$imap->owner = $user->id;
		$imap->hostname = 'imap.group-office.com';
		$imap->port = 143;
		$imap->encryption = 'tls';
		$imap->username = 'test@intermesh.nl';
		$imap->password = 'T3stusr1!';
		$imap->smtpAccount = $smtpAccount;		
		
		if(!$imap->save()) {
			echo "Failed!\n";
			var_dump($imap->getValidationErrors());
			GO()->getDbConnection()->rollBack();
			return;
		}
		

		
		$user = new User();
		$user->username = 'merijn';
		$user->password = 'merijn';
		if(!$user->save()) {
			echo "Failed!\n";
			var_dump($user->getValidationErrors());
			GO()->getDbConnection()->rollBack();
		}
		
		$contact = new \GO\Modules\GroupOffice\Contacts\Model\Contact();
		$contact->userId = $user->id;
		$contact->firstName = 'Merijn';
		$contact->lastName = 'Schering';
		$contact->emailAddresses = [['email'=>'admin@intermesh.dev']];
		if(!$contact->save()) {
			echo "Failed!\n";
			var_dump($contact->getValidationErrors());
			GO()->getDbConnection()->rollBack();
		}
		
		
		
//		$smtpAccount = new \GO\Modules\GroupOffice\Email\Model\SmtpAccount();
//		$smtpAccount->fromEmail = 'admin@intermesh.dev';
//		$smtpAccount->fromName = 'Admin';
//		$smtpAccount->hostname = 'localhost';
//		$smtpAccount->port = 25;
//		$smtpAccount->encryption = null;
//		$smtpAccount->username = "";
//		$smtpAccount->password = "";		
//		$smtpAccount->ownerUserId = $user->id;
//		if(!$smtpAccount->save()) {
//			echo "Failed!\n";
//			var_dump($smtpAccount->getValidationErrors());
//			GO()->dbConnection()->rollBack();
//			return;
//		}
//		
//		$imap = new \GO\Modules\GroupOffice\Email\Model\ImapAccount();
//		$imap->owner = $user->id;
//		$imap->hostname = 'localhost';
//		$imap->port = 143;
//		$imap->encryption = null;
//		$imap->username = 'admin@intermesh.dev';
//		$imap->password = 'admin';
//		$imap->smtpAccount = $smtpAccount;
//		
//		
//		if(!$imap->save()) {
//			echo "Failed!\n";
//			var_dump($imap->getValidationErrors());
//			GO()->dbConnection()->rollBack();
//			return;
//		}
//		
		GO()->getDbConnection()->commit();
		echo "Success!\n";
		});
	}
}