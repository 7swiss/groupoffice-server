<?php

namespace IFW\Orm;

use DateTime;
use GO\Core\Users\Model\Group;
use GO\Core\Users\Model\User;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Model\EmailAddress;
use PHPUnit_Framework_TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class RelationParentTest extends PHPUnit_Framework_TestCase {

	public function test() {

		$contact = new Contact();
		$contact->name = 'Test contact';
		
		$emailAddress = new EmailAddress();
		$emailAddress->email = 'test@intermesh.nl';
		$emailAddress->type = 'work';
		
		$contact->emailAddresses[] = $emailAddress;
		
		
		
		$this->assertEquals($emailAddress->contact, $contact);
		
		
		$contact->save();
		//now when reading		
		$contactId = $contact->id;
		
		
		$contact = Contact::findByPk($contactId);
		
		$firstEmail = $contact->emailAddresses[0];
		
		$this->assertEquals($firstEmail->contact, $contact);
		
		$contact->deleteHard();
		
	}
}
