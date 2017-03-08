<?php

namespace IFW\Db;

use GO\Core\Users\Model\User;
use GO\Modules\Contacts\Model\Contact;
use PHPUnit\Framework\TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class AbstractRecordPermissionsTest extends \PHPUnit\Framework\TestCase {

	private function _testCreateUser(){
		//Set's all roles on test user.
		$user = User::find(['username' => 'unittest'])->single();
		
		if($user) {			
			$user->delete(true);
		}
		
//		if (!$user) {
			$user = new User();
			$user->username = 'unittest';
			$user->password = 'Test123!';
//		}

		$user->save();

		$success = $user->save() !== false;
		
		$this->assertEquals(true, $success);
		
		return $user;
	}
	
	public function testPermissions(){
		
		//login as admin
//		$admin = User::findByPk(1);
//		$admin->setCurrent();		
//		$this->assertEquals($admin->id, \IFW\\IFW::app()->auth()->user()->id);
//		
//		
//		$user = $this->_testCreateUser();
//		
//		$contact = new Contact();
//		$contact->firstName = 'Test';
//		$contact->lastName = date('YmdGis');
//		if(!$contact->save()) {
//			throw new \Exception("Could not save contact");
//		}
//		
//		$contactId = $contact->id;
//		
//		$user->setCurrent();
//		
//		$this->setExpectedException(\IFW\Exception\Forbidden::class);
//		
//		//Should throw forbidden because user "test" can't read.
//		$notFound = Contact::findByPk($contactId);
//		
//		$this->assertEquals(false, $notFound);
//		
//		$contact->firstName = 'Test modified';
//		$contact->save();
//		
//		
//		$admin->setCurrent();	
//		
//		$success = $contact->delete(true);
//		$this->assertEquals(true, $success);
//		
//		$success = $user->delete(true);
//		$this->assertEquals(true, $success);
	}
}