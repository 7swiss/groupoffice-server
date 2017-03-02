<?php

namespace GO\Modules\GroupOffice\ICalendar\Model;

use DateTime;
use IFW\Orm\Record;
use IFW\Orm\Query;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Reader;
use Sabre\VObject\RecurrenceIterator;

/**
 * The VObject model
 *
 * @property string $expandedUntil Recurring events are expanded until this date. Tasks are created as single events and not calculated based on the rrule. *
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class VObject extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $accountId;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $modifiedAt;

	/**
	 * 
	 * @var string
	 */							
	public $data;

	/**
	 * Unique identifier of the calendar event. Required for importing!
	 * @var string
	 */							
	public $uid;

	/**
	 * First occurence of the event or event recurring series.
	 * @var \DateTime
	 */							
	public $firstOccurrence;

	/**
	 * Last occurence of the event or event recurring series.
	 * @var \DateTime
	 */							
	public $lastOccurrence;

	 /**
     * We need to specify a max date, because we need to stop *somewhere*
     *
     * On 32 bit system the maximum for a signed integer is 2147483647, so
     * MAX_DATE cannot be higher than date('Y-m-d', 2147483647) which results
     * in 2038-01-19 to avoid problems when the date is converted
     * to a unix timestamp.
     */
    const MAX_DATE = '2038-01-01';
		
		protected static function defineRelations() {
			
			self::hasOne('account', Account::class, ['accountId' => 'id']);
			parent::defineRelations();
		}


//	public static function findByICalendarData($data, DateTime $start, DateTime $end) {
//
//		$vcalendar = Reader::read($data);
//		$vcalendar->expand($start, $end);
//
//		$events = [];
//
//		foreach ($vcalendar->vevent as $vevent) {
//
////			var_dump($vevent);
//
//			$event = new Event();
//			$event->summary = (string) $vevent->summary;
//			$event->start = $vevent->dtstart->getDateTime();
//			$event->end = $vevent->dtend->getDateTime();
//
//			$events[] = $event;
//		}
//
//		return $events;
//	}

	/**
	 * Parses some information from calendar objects, used for optimized
	 * calendar-queries.
	 *
	 * Returns an array with the following keys:
	 *   * etag - An md5 checksum of the object without the quotes.
	 *   * size - Size of the object in bytes
	 *   * componentType - VEVENT, VTODO or VJOURNAL
	 *   * firstOccurence
	 *   * lastOccurence
	 *   * uid - value of the UID property
	 *
	 * @param string $calendarData
	 * @return array
	 */
	public static function createFromIcalendarData($accountId, $calendarData) {
		
		$vObject = Reader::read($calendarData);
		

		$componentType = null;
		$component = null;
		$firstOccurence = null;
		$lastOccurence = null;
		$uid = null;
		foreach ($vObject->getComponents() as $component) {
			if ($component->name !== 'VTIMEZONE') {
				$componentType = $component->name;
				$uid = (string) $component->UID;
				break;
			}
		}
		if (!$componentType) {
			throw new BadRequest('Calendar objects must have a VJOURNAL, VEVENT or VTODO component');
		}
		if ($componentType === 'VEVENT') {
			$firstOccurence = $component->DTSTART->getDateTime();
			// Finding the last occurence is a bit harder
			if (!isset($component->RRULE)) {
				if (isset($component->DTEND)) {
					$lastOccurence = $component->DTEND->getDateTime();
				} elseif (isset($component->DURATION)) {
					$endDate = clone $component->DTSTART->getDateTime();
					$endDate->add(DateTimeParser::parse($component->DURATION->getValue()));
					$lastOccurence = $endDate;
				} elseif (!$component->DTSTART->hasTime()) {
					$endDate = clone $component->DTSTART->getDateTime();
					$endDate->modify('+1 day');
					$lastOccurence = $endDate;
				} else {
					$lastOccurence = $firstOccurence;
				}
			} else {
				$it = new RecurrenceIterator($vObject, (string) $component->UID);
				$maxDate = new DateTime(self::MAX_DATE);
				if ($it->isInfinite()) {
					$lastOccurence = $maxDate;
				} else {
					$end = $it->getDtEnd();
					while ($it->valid() && $end < $maxDate) {
						$end = $it->getDtEnd();
						$it->next();
					}
					$lastOccurence = $end;
				}
			}
		}

//		return [
//				//'etag' => md5($calendarData),
//				//'size' => strlen($calendarData),
//				'componentType' => $componentType,
//				'firstOccurence' => $firstOccurence,
//				'lastOccurence' => $lastOccurence,
//				'uid' => $uid,
//		];
		
		$now = new \DateTime();
		
		//don't import old events
		if($now > $lastOccurence) {
			return;
		}
		
		$event = self::find(['accountId' => $accountId, 'uid'=>$uid])->single();
		
		if(!$event) {
			$event = new self;
		}
		
		$event->accountId = $accountId;
		$event->uid = $uid;
		$event->data = $calendarData;
		$event->firstOccurrence = $firstOccurence;
		$event->lastOccurrence = $lastOccurence;
		$event->modifiedAt = isset($component->{"LAST-MODIFIED"}) ? $component->{"LAST-MODIFIED"} : new DateTime();
		
		return $event->save();		
	}
	
	/**
	 * Get all VObjects for a user in a given time period
	 * 
	 * Recurring events are expanded
	 * 
	 * @param int $userId
	 * @param DateTime $start
	 * @param DateTime $end
	 * @return VEvent[]
	 */
	public static function findVEvents(\DateTime $start, \DateTime $end, $query = null) {
		$query = Query::normalize($query)						
						->joinRelation('account')
						->andWhere(['<', ['firstOccurrence' => $end]])
						->andWhere(['>=', ['lastOccurrence' => $start]]);

		$objects = VObject::find($query);

		$r = [];

		foreach ($objects as $object) {
			$vcalendar = Reader::read($object->data);
			$vcalendar->expand($start, $end);

			if ($vcalendar->vevent == null) {
				//can happen when a recurring event was selected but does not occur.
				continue;
			}
			foreach ($vcalendar->vevent as $vevent) {
				$vevent->{"X-GO-USER-ID"} = $object->account->createdBy;
				$r[] = $vevent;
			}
		}
		
		return $r;
	}
}