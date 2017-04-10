<?php
namespace GO\Modules\Calendar;

use GO\Modules\GroupOffice\Calendar\Model\Calendar;

class CalendarTest extends AbstractCalendarCase {



	function testCreateCalendar() {

	

		$calendar = new Calendar();
		$calendar->name = 'Test calendar';
		$calendar->color = '000000';

		$this->assertTrue($calendar->save());
   }

	function testReadCalendar() {

		$calendar = Calendar::findByPk(1);
		
		$this->assertTrue($calendar instanceof Calendar);
	}

	function testListCalendar() {

	}
	
	function testShareCalendar() {
		$myId = 1;
		$henkId = 2;

	}

	function testChangeCalendar() {
		$calendar = Calendar::findByPk(1);
		$calendar->name = 'My new name';

		$this->assertTrue($calendar->save());

//		$calendar = Calendar::findByPk(2);
//		$calendar->name = 'Pete\'s new name';
//		$calendar->color = 'E4562B';

		//$this->assertTrue($calendar->save());
	}

	function testChangeSharedCalendar() {

	}

	function testRemoveCalendar() {

	}


}