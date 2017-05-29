<?php
namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Util\DateTime;

class EventTest extends \GO\Utils\ModuleCase {

	protected $adminCalendar;
	protected $henkCalendar;

	private $eventAllDay = ['event' => [
		'title' => 'Test Event',
		'startAt' => '2017-04-06T11:00:00.000Z',
		'endAt' => '2017-04-06T11:00:00.000Z',
		'location' => 'At Home',
		'visibility' => 2,
		'allDay' => true
	]];

	private $eventInvite = ['event' => [
		'uid' => '555-002@phpunit',
		'title' => 'Invite Event',
		'startAt' => '2017-04-01T16:00:00.000Z',
		'endAt' => '2017-04-01T17:00:00.000Z',
		'location' => 'At Work',
		'visibility' => 2,
		'attendees' => [
			['email' => 'piet@phpunit.dev', 'role' => 1],
			['email' => 'henk@phpunit.dev', 'role' => 1],
		],
		'allDay' => false
	]];

	private $eventRecurring = ['event' => [
		'uid' => '555-003@phpunit',
		'title' => 'Recurring Event',
		'startAt' => '2017-04-07T11:00:00.000Z',
		'endAt' => '2017-04-07T12:00:00.000Z',
		'location' => 'In Space',
		'visibility' => 2,
		'allDay' => true,
		'recurrenceRule' => [
			'frequency' => "WEEKLY",
			'interval' => 1,
			'until' => "2017-06-29"
		]
	]];
	private $recurDeleteAt = '2017-04-21';
	private $recurDeleteFrom = '2017-05-26';
	private $recurDeleteFromNext = '2017-06-03'; // should not exist
	private $recurEditAt = '2017-04-14';
	private $recurEditFrom = '2017-04-28';
	private $recurEditFromNext = '2017-05-05'; // should be same as previous

	function setUp() {
		if(empty($this->adminCalendar)) {
			$cal = new Calendar();
			$cal->name = 'Admin events';
			$cal->save();
			$this->adminCalendar = $cal;
		}
		if(empty($this->henkCalendar)) {
			$this->changeUser('henk');
			$cal = new Calendar();
			$cal->name = 'Henk events';
			$cal->save();
			$this->henkCalendar = $cal;
		}
		$this->changeUser('admin'); // make sure we start each test as admin
	}

	function tearDown() {
		//new every test
//		$this->adminCalendar->delete();
//		$this->henkCalendar->delete();
	}

	private function crud($calendar) {
		//create
		$calEvent = $calendar->newEvent();
		$calEvent->setValues($this->eventAllDay); // TODO test set values directly
		$this->assertTrue($calEvent->save());
		$this->assertTrue($calEvent->getIsOrganizer());
		$this->assertEquals($this->currentUser()->email, $calEvent->event->organizerEmail);

		$eventId = $calEvent->eventId;
		//read
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId,'calendarId'=>$calendar->id]);

		$this->assertNotEmpty($calEvent);
		$this->assertTrue($calEvent instanceof CalendarEvent);
		$this->assertNotEmpty($calEvent->event);
		$this->assertTrue($calEvent->event instanceof Event);

		$this->assertEquals($this->eventAllDay['event']['title'], $calEvent->event->title);
		$this->assertFalse($calEvent->event->isRecurring);

		//update
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId,'calendarId'=>$calendar->id]);
		$this->assertNotEmpty($calEvent);
		$calEvent->event->title = 'Change the title'; // todo change more values
		$calEvent->event->visibility = Visibility::cPrivate;
		$this->assertTrue($calEvent->save());

		//delete
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId,'calendarId'=>$calendar->id]);
		$this->assertTrue($calEvent->delete());
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId,'calendarId'=>$calendar->id]);
		$this->assertEmpty($calEvent);
	}

	function testCrudEvent() {

		$this->crud($this->adminCalendar);

	}

	function testCrudAsUser() {

		$this->changeUser('henk');
		$this->crud($this->henkCalendar);
	}

//	function testEventAttachment() {
//
//	}
/*
	function testInvitePeople() {
		$calEvent = $this->adminCalendar->newEvent();
		$calEvent->setValues($this->eventInvite);
		$this->assertTrue($calEvent->save());

		//TODO
		// - check mail send

		// - check mail received
		// - check no response status
		// - accept invite piet
		// - check accept piet
		// - decline invite henk
		// - check decline henk


		//delete invited
		$this->changeUser('henk');
		$inviteEvent = CalendarEvent::findByPk(['calendarId' => $this->adminCalendar->id, 'eventId' => $inviteId]);
		$inviteEvent->accept();
		$inviteEvent->delete();
	}
*/
	function testRecurringEvent() {
		$calEvent = $this->adminCalendar->newEvent();
		$calEvent->setValues($this->eventRecurring);
		$this->assertTrue($calEvent->save());
		$eventId = $calEvent->eventId;
		$calendarId = $this->adminCalendar->id;

		// create 1 exception
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId, 'calendarId'=>$calendarId]);
		$calEvent->addRecurrenceId(new DateTime($this->recurDeleteAt));
		$this->assertTrue($calEvent->delete());

		// delete from here
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId, 'calendarId'=>$calendarId]);
		$calEvent->addRecurrenceId(new DateTime($this->recurDeleteFrom));
		$this->assertTrue($calEvent->deleteFromHere());

		// update 1 occurence
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId, 'calendarId'=>$calendarId]);
		$this->assertEquals($this->eventRecurring['event']['title'], $calEvent->event->title);
		$instance = $calEvent->addRecurrenceId(new DateTime($this->recurEditAt));
		$calEvent->event->title = 'Recur Update 1';
		$calEvent->event->startAt = '2017-04-07T11:30:00+0000';
		$calEvent->event->endAt = '2017-04-07T12:30:00+0000';
		$this->assertTrue($instance->isNew());
		$this->assertTrue($calEvent->save());

		$instance = $calEvent->addRecurrenceId(new DateTime($this->recurEditAt));
		$this->assertEquals('2017-04-07T11:30:00+0000', $instance->patch->startAt->format(\DateTime::ISO8601));
		$this->assertEquals('2017-04-07T12:30:00+0000', $instance->patch->endAt->format(\DateTime::ISO8601));

		// change this 1 occurence again
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId, 'calendarId'=>$calendarId]);
		$this->assertEquals($this->eventRecurring['event']['title'], $calEvent->event->title); 
		$instance = $calEvent->addRecurrenceId(new DateTime($this->recurEditAt));
		$this->assertEquals('Recur Update 1', $instance->patch->title);
		$this->assertFalse($instance->isNew());
		$this->assertFalse($instance->patch->isNew());
		$calEvent->event->title = 'Recur Update 2';
		$calEvent->event->startAt = '2017-04-07T11:30:00+0000';
		$calEvent->event->endAt = '2017-04-07T12:30:00+0000';
		$this->assertTrue($calEvent->save());

		$instance = $calEvent->addRecurrenceId(new DateTime($this->recurEditAt));
		$this->assertEquals('Recur Update 2', $instance->patch->title);
		$this->assertEquals('2017-04-07T11:30:00+0000', $instance->patch->startAt->format(\DateTime::ISO8601));
		$this->assertEquals('2017-04-07T12:30:00+0000', $instance->patch->endAt->format(\DateTime::ISO8601));
		$this->assertEquals($this->adminCalendar->id, $calEvent->calendarId);

		// update from here (new series)
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId, 'calendarId'=>$calendarId]);
		$calEvent->addRecurrenceId(new DateTime($this->recurEditFrom));
		$calEvent->event->title = 'Recur Update 3';
		$this->assertTrue($calEvent->saveFromHere());
		//Test new series

		// list occurrences
		$store = CalendarEvent::findRecurring(
			new DateTime($this->eventRecurring['event']['startAt']),
			new DateTime($this->eventRecurring['event']['recurrenceRule']['until']),
			(new \IFW\Orm\Query())->where('calendarId', $calendarId)
		);
		$store->setReturnProperties('*,event');
		$list = $store->toArray();

		$this->assertCount(6, $list, 'There should be 6 occurrences');
		$byDate = [];
		foreach($list as $instance) {
			$byDate[substr($instance['recurrenceId'], 0, 10)] = $instance;
		}
		
		$this->assertArrayNotHasKey($this->recurDeleteAt, $byDate);
		$this->assertArrayNotHasKey($this->recurDeleteFrom, $byDate);
		$this->assertArrayNotHasKey($this->recurDeleteFromNext, $byDate);
		$this->assertArrayHasKey($this->recurEditAt, $byDate); // is override
		$this->assertArrayHasKey($this->recurEditFrom, $byDate); // is new series
		$this->assertArrayHasKey($this->recurEditFromNext, $byDate);


//		$this->assertEquals($byDate[$this->recurEditAt]['event']['title'], 'Recur Update 2');
//		$this->assertEquals($byDate[$this->recurEditFrom]['event']['title'], 'Recur Update 3');
//		$this->assertEquals($byDate[$this->recurEditFromNext]['event']['title'], 'Recur Update 3');

		//TODO: check list
	}

}