<?php
namespace GO\Modules\Instructiefilm\Elearning\Model;

class ParticipantTest extends \GO\Utils\ModuleCase {

	function testRegistrationNumber() {
		
		$language = Language::find()->single();
		
		$participant1 = new Participant();
		$participant1->language = $language;
		$participant1->firstName = 'Jan';
		$participant1->lastName = 'Test';
		$participant1->dateOfBirth = \DateTime::createFromFormat('Y-m-d', '1980-08-08');
		if(!$participant1->save()) {
			var_dump($participant1->getValidationErrors());
		}
		
		
		$participant2 = new Participant();
		$participant2->language = $language;
		$participant2->firstName = 'Jan';
		$participant2->lastName = 'Test';
		$participant2->dateOfBirth = \DateTime::createFromFormat('Y-m-d', '1980-08-08');
		$participant2->save();
		
		$this->assertEquals($participant1->registrationNumber + 2, $participant2->registrationNumber + 1);
		
	}
}
