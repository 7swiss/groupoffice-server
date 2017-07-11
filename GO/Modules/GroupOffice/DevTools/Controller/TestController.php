<?php
namespace GO\Modules\GroupOffice\DevTools\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Model\EmailAddress;

class TestController extends Controller {
	public function test() {
		$contact = new Contact();
		
		$contact->name = 'test';
		$contact->isOrganization = true;
		
		$emailAddress = new EmailAddress();
		$emailAddress->email = 'test@intermesh.nl';
		$emailAddress->type = 'work';
		
		$contact->emailAddresses[] = $emailAddress;
		
		$contact->save();
		
		
		$contactId = $contact->id;
		
		
		$contact = Contact::findByPk($contactId);
		
		$equal = $contact->emailAddresses[0]->contact == $contact;
		
//		var_dump($equal);
		
		
		$this->renderModel($contact);
		
//				$user = User::find(['username' => 'unittest'])->single();
//
//		//Testing relational setters		
//		$groups = Group::find();		
//
//		foreach ($groups as $group) {
//			$allGroups[] = $group;
//			$groupArr = $group->toArray('id,name');
//			unset($groupArr['className']);
//			$groupAttributes[] = $groupArr;
//		}
//	
//
//		//do it again but with models instead of primary keys
//		$user->groups = $allGroups;
//		
//		$success = $user->save() !== false;
//		
//		$this->renderModel($user);
		
	}
}
