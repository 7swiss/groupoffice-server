<?php
namespace GO\Modules\GroupOffice\Calendar\Model;


class CalendarTest extends \GO\Utils\ModuleCase {

	function testCRUDCalendar() {

		$calendar = new Calendar();
		$calendar->name = 'Test calendar';
		$calendar->color = '000000';

		$this->assertTrue($calendar->save());
		$calendarId = $calendar->id;

		$calendar = Calendar::findByPk($calendarId);
		$this->assertNotEmpty(Calendar::findByPk($calendarId));

		$this->assertTrue($calendar instanceof Calendar);

		$calendar->name = 'New name';

		$this->assertTrue($calendar->save());

		$calendar = Calendar::findByPk($calendarId);
		$this->assertNotEmpty(Calendar::findByPk($calendarId));

		$this->assertTrue($calendar->delete());
		// there is no soft delete

		$calendar = Calendar::findByPk($calendarId);
		$this->assertEmpty($calendar);
   }


	function testListCalendar() {
		$all = Calendar::find();
		$this->assertCount(0, $all);

		$calendar = new Calendar();
		$calendar->name = 'Test calendar';
		$calendar->color = '000000';

		$this->assertTrue($calendar->save());
		$all = Calendar::find();
		$this->assertCount(1, $all);

		$this->changeUser('henk');
		$all = Calendar::find();
		$this->assertCount(0, $all);
		$this->changeUser('admin');
	}
	
	function testShareCalendar() {

		$henk = $this->getUser('henk');

		$this->assertTrue($henk instanceof \GO\Core\Users\Model\User);

		$calendar = new Calendar();
		$calendar->name = 'Test calendar';
		$calendar->color = '000000';
		$calendar->groups[] = ['groupId' => $henk->group->id, 'write' => true];
		$this->assertTrue($calendar->save());

		$calendarId = $calendar->id;

		$this->changeUser('henk');

		$calendar = Calendar::findByPk($calendarId);
		$this->assertNotEmpty($calendar);

		$this->assertFalse($calendar->ownedBy == $henk->id);
		//$this->assertTrue($cal->getPermissions()->can('write')); // fails??
		
		$calendar->color = '111111';
		$this->assertTrue($calendar->save());

		$this->changeUser('admin');
	}

	function testRemoveCalendar() {
		$cal = new Calendar();
		$cal->name = 'del test';
		$this->assertTrue($cal->save());
		$id = $cal->id;
		
		$this->assertTrue($cal->delete());
		$this->assertEmpty(Calendar::findByPk($id));
	}


}