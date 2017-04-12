<?php
namespace GO\Modules\GroupOffice\Calendar\Model;

class EventTest extends \GO\Utils\ModuleCase {

	protected static $module = '\GO\Modules\GroupOffice\Calendar\Module';

	protected $adminCalendar;

	private $eventAllDay = [
		'uuid' => '555-001@phpunit',
		'title' => 'Test Event',
		'endAt' => '2017-04-06T11:00:00.000Z',
		'startAt' => '2017-04-06T11:00:00.000Z',
		'location' => 'At Home',
		'visibility' => 2,
		'allDay' => true
	];

	private $eventInvite = [
		'uuid' => '555-002@phpunit',
		'title' => 'Invite Event',
		'endAt' => '2017-04-01T16:00:00.000Z',
		'startAt' => '2017-04-01T17:00:00.000Z',
		'location' => 'At Work',
		'visibility' => 2,
		'attendees' => [
			['email' => 'admin@intermesh.dev'],
			['email' => 'henk@phpunit.dev'],
		],
		'allDay' => false
	];

	private $eventRecurring = [
		'uuid' => '555-003@phpunit',
		'title' => 'Recurring Event',
		'endAt' => '2017-04-07T11:00:00.000Z',
		'startAt' => '2017-04-07T11:00:00.000Z',
		'location' => 'In Space',
		'visibility' => 2,
		'allDay' => true
	];

	function setUp() {
		if(empty($this->adminCalendar)) {

			$cal = new Calendar();
			$cal->name = 'Admin events';
			$cal->save();
			$this->adminCalendar = $cal;
		}
		$this->changeUser('admin'); // make sure we start each test as admin
	}

	function testCreateEvent() {
		$attendee = $this->adminCalendar->newEvent();
		$attendee->event->setValues($this->eventAllDay);

		$this->assertTrue($attendee->save());
		$this->assertEquals($this->currentUser()->email, $attendee->event->organizerEmail);
	}

	function testReadEvent() {
		$groupId = $this->adminCalendar->ownedBy;
		$eventId = 1;

		$attendee = Attendee::findByPk(['eventId'=>$eventId,'groupId'=>$groupId]);

		$this->assertNotEmpty($attendee);
		$this->assertTrue($attendee instanceof Attendee);
		$this->assertNotEmpty($attendee->event);
		$this->assertTrue($attendee->event instanceof Event);

		$this->assertEquals($this->eventAllDay['title'], $attendee->event->title);
		$this->assertFalse($attendee->event->isRecurring);

	}

//	function testUpdateEvent() {
//		$groupId = $this->adminCalendar->ownedBy;
//		$eventId = 1;
//		$attendee = Attendee::findByPk(['eventId'=>$eventId,'groupId'=>$groupId]);
//		$this->assertNotEmpty($attendee);
//		$attendee->event->title = 'Change the title';
//		$attendee->event->visibility = Visibility::cPrivate;
//		$success = $attendee->event->save();
//		$id = $attendee->event->id;
//
//		$this->assertTrue($success);
//		$event = Event::findByPk($id);
//		$this->assertEquals('Change the title', $event->title);
//	}
//
//	function testInvitePeople() {
//		$attendee = $this->adminCalendar->newEvent();
//		$attendee->event->setValues($this->eventInvite);
//		$success = $attendee->save();
//
//		$this->assertTrue($success);
//
//		//TODO
//	}
//
//	function testDeleteEvent() {
//		//new
//		$event = new Event();
//		$event->setValues($this->eventAllDay);
//		$this->assertTrue($event->save());
//		$id = $event->id;
//		//delete mine
//		$this->assertTrue($event->delete());
//		$this->assertEmpty(Event::findByPk($id));
//
//		$event = new Event();
//		$event->setValues($this->eventInvite);
//		$this->assertTrue($event->save());
//		$inviteId = $event->id;
//		//delete invited
//		$this->changeUser('henk');
//		$inviteEvent = Event::findByPk($inviteId);
//		$inviteEvent->accept();
//		$inviteEvent->delete();
//	}
//
//	function testRecurringEvent() {
//		$event = new Event();
//		$event->setValues($this->eventRecurring);
//		$success = $event->save();
//		//TODO
//	}

	function testAcceptEvent() {
		
	}
}