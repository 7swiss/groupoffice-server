<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Orm\Record;
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

	// DEFINITION
	public static function tableName() {
		return 'calendar_recurrence_rule';
	}
	protected static function defineRelations() {
		self::hasOne('event', Event::class, ['eventId' => 'id']);
		self::hasMany('exceptions', EventException::class, ['eventId' => 'recurrenceEventId']);
	}

	protected static function internalGetPermissions() {
		return new ViaRelation('event');
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
		$result = [];

		$this->getIterator()->fastForward($start);
		while($startAt = $this->getIterator()->current()) {
			if($startAt > $end)
				break;
			$event = clone $this->event;
			$event->setStartTime($startAt);
			$result[] = $event;
			$this->getIterator()->next();
		}
		return $result;
	}
	
}
