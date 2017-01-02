<?php

namespace IFW\Db;

use DateTime;
use GO\Modules\Tasks\Model\WorkingPeriod;
use GO\Modules\Tasks\Model\WorkingWeek;
use PHPUnit_Framework_TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class WorkingWeekTest extends PHPUnit_Framework_TestCase {

//	
//	public function testTimes() {
//		$workingWeek = new WorkingWeek();
//		$time = $workingWeek->next(60);
////		echo $time->format('c');
//		var_dump($time);
//		
//	}

	public function testTimes() {
		return;

		date_default_timezone_set("Europe/Amsterdam");

		$start = new DateTime("2015-05-01T16:45");
//		$end = new DateTime("2015-05-06 23:00");

		$workingWeek = new WorkingWeek();
		$workingWeek->periods = [
				new WorkingPeriod(1, 8, 0, 12, 30), //mon
				new WorkingPeriod(1, 13, 0, 17, 30), //mon
				new WorkingPeriod(3, 8, 0, 12, 30),
				new WorkingPeriod(3, 13, 0, 17, 30),
				new WorkingPeriod(4, 8, 0, 12, 30),
				new WorkingPeriod(4, 13, 0, 17, 30),
				new WorkingPeriod(5, 8, 0, 12, 30),
				new WorkingPeriod(5, 13, 0, 17, 30)
		];

		$workingWeek->blockTime(new DateTime("2015-05-04 8:00"), new DateTime("2015-05-04 17:30"));
//		$workingWeek->blockTime(new DateTime("2015-05-06 10:00"), new DateTime("2015-05-06 11:30"));

		$workingWeek->setTimePointer($start);

		$taskDurations = [60, 180];//, 15, 120, 30, 60, 120, 120, 45, 60, 60, 30, 60, 30, 15, 120, 30, 60, 120, 120, 45, 60, 60, 30, 86400];

		for ($i = 0, $c = count($taskDurations); $i < $c; $i++) {
			$dts = $workingWeek->next($taskDurations[$i]);
			$times[$i] = $dts['start']->format('c') . ' - ' . $dts['end']->format('c') . ' (' . $taskDurations[$i] . ')';
		}

		var_export($times);
//		$this->assertEquals(array(
//				0 => '2015-04-30T12:15:00+02:00 - 2015-04-30T13:45:00+02:00 (60)',
//				1 => '2015-04-30T13:45:00+02:00 - 2015-04-30T14:15:00+02:00 (30)',
//				2 => '2015-04-30T14:15:00+02:00 - 2015-04-30T14:30:00+02:00 (15)',
//				3 => '2015-04-30T14:30:00+02:00 - 2015-04-30T16:30:00+02:00 (120)',
//				4 => '2015-04-30T16:30:00+02:00 - 2015-04-30T17:00:00+02:00 (30)',
//				5 => '2015-04-30T17:00:00+02:00 - 2015-05-01T08:30:00+02:00 (60)',
//				6 => '2015-05-01T08:30:00+02:00 - 2015-05-01T10:30:00+02:00 (120)',
//				7 => '2015-05-01T10:30:00+02:00 - 2015-05-01T12:30:00+02:00 (120)',
//				8 => '2015-05-01T13:00:00+02:00 - 2015-05-01T13:45:00+02:00 (45)',
//				9 => '2015-05-01T13:45:00+02:00 - 2015-05-01T15:15:00+02:00 (60)',
//				10 => '2015-05-01T15:15:00+02:00 - 2015-05-01T16:15:00+02:00 (60)',
//				11 => '2015-05-01T16:15:00+02:00 - 2015-05-01T16:45:00+02:00 (30)',
//				12 => '2015-05-01T16:45:00+02:00 - 2015-05-04T08:15:00+02:00 (60)',
//				13 => '2015-05-04T08:15:00+02:00 - 2015-05-04T08:45:00+02:00 (30)',
//				14 => '2015-05-04T08:45:00+02:00 - 2015-05-04T09:00:00+02:00 (15)',
//				15 => '2015-05-04T09:00:00+02:00 - 2015-05-04T11:00:00+02:00 (120)',
//				16 => '2015-05-04T11:00:00+02:00 - 2015-05-04T11:30:00+02:00 (30)',
//				17 => '2015-05-04T11:30:00+02:00 - 2015-05-04T12:30:00+02:00 (60)',
//				18 => '2015-05-04T13:00:00+02:00 - 2015-05-04T15:00:00+02:00 (120)',
//				19 => '2015-05-04T15:00:00+02:00 - 2015-05-04T17:00:00+02:00 (120)',
//				20 => '2015-05-04T17:00:00+02:00 - 2015-05-06T08:15:00+02:00 (45)',
//				21 => '2015-05-06T08:15:00+02:00 - 2015-05-06T09:15:00+02:00 (60)',
//				22 => '2015-05-06T09:15:00+02:00 - 2015-05-06T10:15:00+02:00 (60)',
//				23 => '2015-05-06T10:15:00+02:00 - 2015-05-06T10:45:00+02:00 (30)',
//				24 => '2015-05-06T10:45:00+02:00 - 2016-02-10T10:45:00+01:00 (86400)',
//						)
//						, $times);

//
//
//		$availableMins = $workingWeek->getAvailableMinutes($start, $end);
//
//		$this->assertEquals(2160, $availableMins);
	}

}
