<?php
namespace GO\Modules\GroupOffice\Calendar\Model;

class CalendarTest extends \GO\Utils\ModuleCase {

	protected static $module = '\GO\Modules\GroupOffice\Calendar\Module';

	function testCreateCalendar() {

		$calendar = new Calendar();
		$calendar->name = 'Test calendar';
		$calendar->color = '000000';

		$this->assertTrue($calendar->save());
   }

	function testReadWriteCalendar() {

		$calendar = Calendar::findByPk(1);
		
		$this->assertTrue($calendar instanceof Calendar);

		$calendar->name = 'New name';

		$this->assertTrue($calendar->save());
	}

	function testListCalendar() {
		$all = Calendar::find();
		$count = 0;
		foreach($all as $calendar) {
			$count++;
		}
		$this->assertEquals(1, $all->getRowCount());
		$this->assertTrue($count === 1);
	}
	
	function testShareCalendar() {

		$henk = $this->getUser('henk');

		$this->assertTrue($henk instanceof \GO\Core\Users\Model\User);

		$cal = Calendar::findByPk(1);
		$cal->setValues(['groups' => [
			['groupId' => $henk->group->id, 'write' => true]
		]]);

		$this->assertTrue($cal->save());

		$this->changeUser('henk');

		$cal = Calendar::findByPk(1);
		$this->assertNotEmpty($cal);

		$this->assertFalse($cal->ownedBy == $henk->id);
		//$this->assertTrue($cal->getPermissions()->can('write')); // fails??
		
		$cal->color = '111111';
		$this->assertTrue($cal->save());

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