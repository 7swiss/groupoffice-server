<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Orm\Record;
use IFW\Orm\Query;
use IFW\Util\DateTime;
use IFW\Auth\Permissions\ViaRelation;

/**
 * This class defines how an Event needs to be recurred
 *
 * @property Event $event The event this rule applies to. 
 * @property-read Datetime $startAt time of first occurence
 * @property int $occurences The amount of times the event will recur.
 * @property string $rrule the rrule string is text @see setRRule() and getRRule()
 * @property-read bool $isInfinite when there is no end to the recurring rule
 * @property-read int $weekStart on what day the week start occuring to the localization (0 = sunday, 1 = monday, etc)
 * 
 * @property Event[] $exceptions exceptions of the event indexed by exception date
 */
class RecurrenceRule extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $eventId;

	/**
	 * see constants.
	 * @var string
	 */							
	public $frequency;

	/**
	 * till when the event will recur.
	 * @var \DateTime
	 */							
	public $until;

	/**
	 * 
	 * @var int
	 */							
	public $occurrences;

	/**
	 * recur every nth time this rule specifies. eg once every 3 weeks instead of every week
	 * @var int
	 */							
	public $interval = 0;

	/**
	 * binary integer were last bit is 'monday' true
	 * @var int
	 */							
	public $byDay;

	/**
	 * binary integer were last bit is 'january' true
	 * @var int
	 */							
	public $byMonth;

	/**
	 * 
	 * @var int
	 */							
	public $byYearday;

	/**
	 * binary integer were last bit is '1st' true
	 * @var int
	 */							
	public $byMonthday;

	/**
	 * binary integer were last bit is '00:xx' true
	 * @var int
	 */							
	public $byHour;

	/**
	 * binary integer were last bit is 'xx:00' true
	 * @var int
	 */							
	public $byMinute;

	/**
	 * binary integer were last bit is 'xx:xx:00' true (only implemented for display)
	 * @var int
	 */							
	public $bySecond;

	/**
	 * binary integer were last bit is '366' true and the first bit '-366' specified the nth occurence
	 * @var int
	 */							
	public $bySetPos = 0;

	const Secondly = 'S';
	const Minutely = 'I';
	const Hourly = 'H';
	const Daily = 'D';
	const Weekly = 'W';
	const Monthly = 'M';
	const Annually = 'Y';
	
	/**
	 * RRule parser that is internally used
	 * @see getIterator()
	 * @var RRuleIterator
	 */
	private $iterator = null;

	/**
	 * the calendar event we are calculation occurrences for
	 * @var CalendarEvent
	 */
	protected $forAttendee = null;

	// DEFINITION
	public static function tableName() {
		return 'calendar_recurrence_rule';
	}
	protected static function defineRelations() {
		self::hasOne('event', Event::class, ['eventId' => 'id']);
		self::hasMany('exceptions', RecurrenceException::class, ['eventId' => 'eventId']);
		self::hasMany('overrides', Event::class, ['eventId' => 'id'])->setQuery((new Query)->select('*'))
				->via(Event::class,['uid'=>'uid']);
	}

	protected function internalValidate() {

		if($this->frequency === null && !$this->markDeleted) {
			return true; // save nothing when there is no frequency
		}
		return parent::internalValidate();
	}

	protected function internalSave() {

		if($this->frequency === null && !$this->markDeleted) {
			return true; // save nothing when there is no frequency
		}
		return parent::internalSave();
	}

	public function forAttendee($calendarEvent) {
		$this->forAttendee = $calendarEvent;
	}

	/**
	 * Close the series including this day
	 * @param DateTime $date
	 */
	public function stopBefore(DateTime $date) {
		$this->until = clone $date;
		$this->until->sub(new \DateInterval('P1D')); // gisteren
	}

	/**
	 * When the RRule has no end time
	 * @return bool true when this recurs forever
	 */
	public function isInfinite() {
		return $this->getIterator()->isInfinite();
	}

	private function getIterator() {
		if(empty($this->iterator)) {
			$this->iterator = ICalendarHelper::makeRecurrenceIterator($this);
		}
		return $this->iterator;
	}

	/**
	 *
	 * @param \Datetime $start
	 * @param \Datetime $end
	 * @return \Datetime[]
	 */
	public function getOccurences($start, $end) {
		if($this->forAttendee === null) {
			throw new \Exception('Can get occurrences, select Attendee first');
		}
		$result = [];
		$this->getIterator()->fastForward($start);
		while($recurrenceId = $this->getIterator()->current()) {
			if($recurrenceId > $end)
				break;
			$calEvent = clone $this->forAttendee;
			$calEvent->event = clone $this->forAttendee->event;
			$calEvent->addRecurrenceId($recurrenceId);
			$result[$recurrenceId->getTimestamp()] = $calEvent;
			$this->getIterator()->next();
		}
		foreach($this->exceptions as $exception) { // @todo fetch only exceptions between $start and $end
			if(isset($result[$exception->at->getTimestamp()])) {
				unset($result[$exception->at->getTimestamp()]);
			}
		}
		foreach($this->overrides as $exception) { // @todo fetch only exceptions between $start and $end
			if(!empty($exception->recurrenceId) && isset($result[$exception->recurrenceId->getTimestamp()])) {
				unset($result[$exception->recurrenceId->getTimestamp()]);
			}
		}
		return $result;
	}

	/**
	 * Add exception to the current event, and pass the replacementEvent if any
	 * Replacement event will not be saved
	 * @param DateTime $recurrenceId time to except the recurrent
	 * @param array $attrs the replacement attributes
	 * @return bool successful
	 */
	public function addException(DateTime $recurrenceId, $attrs = null) {
		$exception = new RecurrenceException();
		empty($attrs) ? $exception->isRemoved = true : $exception->setValues($attrs);
		$exception->recurrenceId = $recurrenceId;
		$exception->eventId = $this->eventId;
		$this->exceptions[] = $exception;
	}
	
}