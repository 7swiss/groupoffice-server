<?php

namespace GO\Modules\GroupOffice\DemoData\Controller;

use GO\Core\Blob\Model\Blob;
use GO\Core\Controller;
use GO\Core\Smtp\Model\Account as Account2;
use GO\Core\Users\Model\User;
use GO\Modules\GroupOffice\Contacts\Model\Address;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Imap\Model\Account;
use function GO;

class DemoDataController extends Controller {

	protected function checkAccess() {
		return true;
	}

	public function actionCreate() {

		GO()->getAuth()->sudo(function() {

			GO()->getConfig()->classConfig['IFW\Validate\ValidatePassword'] = ['enabled' => false];

			GO()->getDbConnection()->beginTransaction();

			if (!User::find(['username' => 'test'])->single()) {
				$user = new User();
				$user->username = 'test';
				$user->password = 'test';
				$user->email = 'test@intermesh.nl';
				if (!$user->save()) {
					echo "Failed!\n";
					var_dump($user->getValidationErrors());
					GO()->getDbConnection()->rollBack();
					return;
				}

				$contact = new Contact();
				$contact->ownedBy = $user->group->id;
				$contact->createdBy = $user->id;
				$contact->firstName = 'Test';
				$contact->lastName = 'User';
				$contact->emailAddresses = [['email' => 'test@intermesh.nl']];
				$contact->save();
				if (!$contact->save()) {
					echo "Failed!\n";
					var_dump($contact->getValidationErrors());
					GO()->getDbConnection()->rollBack();
					return;
				}
			}


			if (!Account2::find(['username' => 'test@intermesh.nl'])->single()) {
				$smtpAccount = new Account2();
				$smtpAccount->fromEmail = 'test@intermesh.nl';
				$smtpAccount->fromName = 'Test user';
				$smtpAccount->hostname = 'smtp.group-office.com';
				$smtpAccount->port = 465;
				$smtpAccount->encryption = 'ssl';
				$smtpAccount->username = 'test@intermesh.nl';
				$smtpAccount->password = 'T3stusr1!';
				$smtpAccount->createdBy = $user->id;
				if (!$smtpAccount->save()) {
					echo "Failed!\n";
					var_dump($smtpAccount->getValidationErrors());
					GO()->getDbConnection()->rollBack();
					return;
				}

				$imap = new Account();
				$imap->createdBy = $user->id;
				$imap->hostname = 'imap.group-office.com';
				$imap->port = 143;
				$imap->encryption = 'tls';
				$imap->username = 'test@intermesh.nl';
				$imap->password = 'T3stusr1!';
				$imap->smtpAccount = $smtpAccount;

				if (!$imap->save()) {
					echo "Failed!\n";
					var_dump($imap->getValidationErrors());
					GO()->getDbConnection()->rollBack();
					return;
				}
			}



//			$user = new User();
//			$user->username = 'merijn';
//			$user->password = 'merijn';
//			if (!$user->save()) {
//				echo "Failed!\n";
//				var_dump($user->getValidationErrors());
//				GO()->getDbConnection()->rollBack();
//			}
//
//			$contact = new \GO\Modules\GroupOffice\Contacts\Model\Contact();
//			$contact->userId = $user->id;
//			$contact->firstName = 'Merijn';
//			$contact->lastName = 'Schering';
//			$contact->emailAddresses = [['email' => 'admin@intermesh.dev']];
//			if (!$contact->save()) {
//				echo "Failed!\n";
//				var_dump($contact->getValidationErrors());
//				GO()->getDbConnection()->rollBack();
//			}

			$intermesh = Contact::find(['name' => 'Intermesh BV'])->single();

			if (!$intermesh) {
				$intermesh = new Contact();
				$intermesh->isOrganization = true;
				$intermesh->name = 'Intermesh BV';
				$intermesh->photoBlob = Blob::fromFile(new \IFW\Fs\File(dirname(__DIR__) . '/Resources/intermesh-logo.png'));

				$intermesh->emailAddresses = [
						['type' => 'work', 'email' => 'info@intermesh.nl'],
						['type' => 'invoicing', 'email' => 'invoice@intermesh.nl']
				];

				$intermesh->phoneNumbers = [
						['type' => 'work', 'number' => '+31 (0) 73 20 46 000']
				];

				$intermesh->addresses = [
						[
								'type' => Address::TYPE_POST,
								'street' => 'Veemarktkade 8',
								'zipCode' => '5222 AE',
								'city' => "'s-Hertogenbosch",
								'country' => 'Nederland'
						]
				];

				$intermesh->language = 'nl';

				if (!$intermesh->save()) {
					echo "Failed!\n";
					var_dump($imap->getValidationErrors());
					GO()->getDbConnection()->rollBack();
					return;
				}
			}

			$merijn = Contact::find(['name' => 'Merijn Schering'])->single();

			if (!$merijn) {
				$merijn = new Contact();
				$merijn->gender = Contact::GENDER_MALE;
				$merijn->firstName = 'Merijn';
				$merijn->lastName = 'Schering';
				$merijn->organizations[] = $intermesh;
				$merijn->emailAddresses = [
						['type' => 'work', 'email' => 'mschering@intermesh.nl']
				];
				$merijn->photoBlob = Blob::fromFile(new \IFW\Fs\File(dirname(__DIR__) . '/Resources/merijn.jpg'));
				if (!$merijn->save()) {
					echo "Failed!\n";
					var_dump($imap->getValidationErrors());
					GO()->getDbConnection()->rollBack();
					return;
				}
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
