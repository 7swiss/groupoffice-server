<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Calendar\Model;

use GO\Core\Orm\Record;

/**
 * This is the default alarm setting for an calendar.
 * It doesn't ring it copies its data to new event created in this calendar.
 *
 * @property int $triggerAt Time to trigger the alarm. If this is set secondsFrom will be ignorerd
 */
class DefaultAlarm extends Record {

	/**
	 * auto increment primary key
	 * @var int
	 */							
	public $id;

	/**
	 * an ISO8601 period string (can be negative)
	 * @var string
	 */							
	public $trigger;

	/**
	 * 1=starttime, 2=endtime 0=none
	 * @var int
	 */							
	public $relativeTo = 0;

	/**
	 * PK of the event this alarm is set on
	 * @var int
	 */							
	public $calendarId;

	const RelativeNone = 0; // use $triggerAt
	const RelativeToStartTime = 1;
	const RelativeToEndTime = 2;
}