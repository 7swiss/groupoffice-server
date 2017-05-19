<?php
namespace GO\Modules\GroupOffice\Calendar\Model;

class AlarmTest extends \GO\Utils\ModuleCase {

	private static $adminCalendar;
	private static $calEvent;

	static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		if(empty(self::$adminCalendar)) {
			$cal = new Calendar();
			$cal->name = 'Admin ringer calendar';
			$cal->save();
			self::$adminCalendar = $cal;
		}
		if(empty(self::$event)) {
			$calEvent = self::$adminCalendar->newEvent();
			$calEvent->event->title = 'Its ringing';
			$calEvent->save();
			self::$calEvent = $calEvent;
		}
	}

	static function tearDownAfterClass() {
		self::$calEvent->delete();
		self::$adminCalendar->delete();

		parent::tearDownAfterClass();
	}

//	function testAddAlarm() {
//		$alarm = new Alarm();
//		$this->event->addAlarms($alarm);
//		$this->event->save();
//	}
//
//	function testTriggerAlarm() {
//
//	}
//
//	function testDismissAlarm() {
//
//	}
//
//	function testRemoveAlarm() {
//
//	}

}