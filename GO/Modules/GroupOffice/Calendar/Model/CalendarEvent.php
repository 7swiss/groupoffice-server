<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Orm\Record;
use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Query;
use IFW\Util\DateTime;

/**
 * This serves as an in between record for the event that is in a calendar.
 * It merged Attendee with Event
 * @property Event $event Event object for this calendar event
 */
class CalendarEvent extends Record {

	public $calendarId;
	public $groupId;
	public $eventId;
	public $deleted;

	public $responseStatus;
	public $role;
	public $email;

	/**
	 * The Date(time) that an instance of a recurring series is occurring
	 * @var \DateTime
	 */
	protected $recurrenceId = null;
	public $singleInstance = null;

	/**
	 * The exception we are saving or deleting.
	 * This is hasMany but can de saved 1 at the time
	 * @var RecurrenceException
	 */
	private $exception;

	public static function tableName() {
		return 'calendar_attendee';
	}

	public static function getPrimaryKey() {
		return ['calendarId', 'eventId'];
	}

	public static function find($query = null) {
		if($query instanceof Query) {
			$query->andWhere('calendarId IS NOT NULL');
			$query->joinRelation('event', 't.*, event.allDay, event.startAt, event.endAt, event.location, event.title', 'LEFT');
//			$query->select('t.*, event.allDay, event.startAt, event.endAt, event.location, event.title')
//					->join(Event::tableName(), 'event', 't.eventId = event.id', 'LEFT');
		}
		return parent::find($query);
	}

	static public function findRecurring($start, $end) {
		$query = new \IFW\Orm\Query();
		$query->joinRelation('recurrenceRule');
		$events = CalendarEvent::find($query);
		$allOccurrences = [];
		foreach($events as $calEvent) {
			$rule = $calEvent->recurrenceRule;
			$rule->forAttendee($calEvent);
			$allOccurrences = array_merge($allOccurrences, $rule->getOccurences($start, $end));
		}
		return new \IFW\Data\Store($allOccurrences);
	}

	protected static function defineRelations() {
		self::hasOne('event', Event::class, ['eventId' => 'id']);
		self::hasOne('recurrenceRule', RecurrenceRule::class, ['eventId' => 'eventId']);
		self::hasOne('calendar', Calendar::class, ['calendarId' => 'id']);
		self::hasMany('alarms', Alarm::class, ['eventId' => 'eventId', 'groupId' => 'groupId']);
	}

	public static function internalGetPermissions() {
		return new ViaRelation('calendar');
	}

	/**
	 * TODO analyse rowCount performance
	 * @return type
	 */
	public function getHasAlarms() {
		return $this->alarms->getRowCount() > 0;
	}

	public function getStartAt() {
		return !empty($this->recurrenceId) ? $this->recurrenceId : $this->event->startAt;
	}

	public function getEndAt() {
		if(empty($this->recurrenceId)) {
			return $this->event->endAt;
		}
		$endAt = clone $this->recurrenceId;
		$endAt->add($this->event->getDuration());
		return $endAt;
	}

	/**
	 * When the recurrenceId of an event was set it represents a single instance
	 * of a recurring event. When editing we create an exception
	 * @return bool
	 */
	public function getIsInstance() {
		return !empty($this->recurrenceId);
	}

	public function getIsOrganizer() {
		return $this->email === $this->event->organizerEmail;
	}

	/**
	 * When this event is an exception for another event.
	 * This is the date for the occurrence
	 */
	public function getRecurrenceId() {
		return $this->recurrenceId; // or start time?
	}

	/**
	 * @param Datetime $recurrenceId
	 * this instance forward. When true we create an exception
	 */
	public function addRecurrenceId($recurrenceId) {

		$this->recurrenceId = $recurrenceId;

		//Apply exception if any
		$exception = Event::find([
			 'id' => $this->eventId,
			 'recurrenceId' => $this->recurrenceId
		])->single();

		if(!empty($exception)) {
			$this->event = $exception;
		}

	}

	protected function internalSave() {

		if($this->event->getIsRecurring()) {
			if($this->getIsInstance() && $this->singleInstance) {
				return $this->saveException($this->recurrenceId, $this->event);
			} else if (!$this->isFirstInSeries()) {
				return $this->saveFromHere($this->startAt);
			}
		}

		return parent::internalSave();

	}
	
	protected function internalDelete($hard) {

		if($this->event->getIsRecurring()) {
			if($this->getIsInstance() && $this->singleInstance) {
				return $this->saveException($this->recurrenceId);
			} else if(!$this->isFirstInSeries()) {
				return $this->deleteFromHere(); // close serie
			}
		}

		return parent::internalDelete($hard);
	}

	protected function isFirstInSeries() {
		$startAt = $this->event->isModified('startAt') ? $this->event->getOldAttributeValue('startAt') : $this->event->startAt;
		return $this->isNew() || ($this->recurrenceId == $startAt);
	}

	/**
	 * Add exception to the current event, and pass the replacementEvent if any
	 * Replacement event will not be saved
	 * @param DateTime $occurrence time to except the recurrent
	 * @param Event $event the replacement attributes
	 * @return bool successful
	 */
	public function saveException(DateTime $occurrence, Event $event = null) {
		if(empty($this->exception)) {
			$this->exception = new RecurrenceException();
		}
		$this->exception->recurrenceId = $occurrence;
		$this->exception->eventId = $this->eventId;
		$this->exception->isRemoved = ($event === null);
		if($event !== null) {
			foreach($event->getModifiedAttributes() as $column => $oldValue) {
				if(in_array($column, RecurrenceException::validAttrs())) {
					$this->exception->{$column} = $event->{$column};
				}
			}
		}
		return $this->exception->save();
	}

	private function cloneMe() {
		$calEvent = new self();
		$properties = $this->toArray();
		$calEvent->setValues($properties);
		$data = $this->event->cloneMe();
		$calEvent->event = $data;
		return $calEvent;
	}

	/**
	 * Set the until time of this recurrence rule and create a new event
	 * with the same recurring rule starting from The occurrence start time
	 */
	private function saveFromHere(DateTime $occurrence) {

		$newSeries = $this->cloneMe();
		$newSeries->event->startAt = clone $occurrence;

		$rrule = new RecurrenceRule();
		$rrule->setValues($this->recurrenceRule->toArray());
		$rrule->eventId = null; // unset to attach to new event
		$newSeries->event->recurrenceRule = $rrule;
		$success = $newSeries->save();

		$startTimeDiff = $this->getOldAttributeValue('startAt')->diff($newSeries->startAt); // Todo
		$startTimeDiff->d = 0;
		$startTimeDiff->m = 0;
		$startTimeDiff->y = 0;
		// Move remove and reattach exceptions
		foreach($this->recurrenceRule->exceptions as $exception) {
			if($exception->recurrenceId < $occurrence) {
				continue;
			}
			if($this->isModified('startAt')) {
				$exception->recurrenceId->add($startTimeDiff);
			}
			$exception->eventId = $newSeries->id;
			$success = $success && $exception->save();
		}

		$this->recurrenceRule->stopBefore($occurrence);
		return $success && $this->recurrenceRule->save();

	}

	private function deleteFromHere() {

		$this->recurrenceRule->stopBefore($this->recurrenceId);
		return $this->recurrenceRule->save();
	}


}