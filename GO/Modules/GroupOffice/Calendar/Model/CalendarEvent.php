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
 * @property Calendar $calendar The calendar this is in
 */
class CalendarEvent extends Record {

	public $calendarId;
	public $groupId;
	public $eventId;

	public $responseStatus;
	public $role;
	public $email;

	private $fromHere = false;

	/**
	 * An exception to the rule or an extra occurence
	 * @var EventInstance
	 */
	private $instance;

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

	static public function findRecurring(DateTime $start, DateTime $end, $query = null) {
		if($query === null) {
			$query = new \IFW\Orm\Query();
		}
		$query->joinRelation('recurrenceRule')->andWhere('frequency IS NOT NULL');
		$events = self::find($query);
		$allOccurrences = [];
		foreach($events as $calEvent) {
			$rule = $calEvent->recurrenceRule;
			$rule->forAttendee($calEvent);
			$allOccurrences += $rule->getOccurences($start, $end);
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

	public function getRecurrenceId() {
		return !empty($this->instance) ? $this->instance->recurrenceId : null;
	}

	public function getStartAt() {
		if(!empty($this->instance)) {
			return $this->instance->startAt;
		}
		return $this->event->startAt;
	}

	public function getEndAt() {
		if(!empty($this->instance)) {
			return $this->instance->endAt;
		}
		return $this->event->endAt;
	}

	/**
	 * @todo analyze rowCount performance
	 * @return bool
	 */
	public function getHasAlarms() {
		return $this->alarms->getRowCount() > 0;
	}

	public function getIsOrganizer() {
		return $this->email === $this->event->organizerEmail;
	}

	/**
	 * This function can only be called on a recurring series. After that the object
	 * represents an instance of the series. This instance is loaded
	 * @param Datetime $recurrenceId
	 */
	public function addRecurrenceId(DateTime $recurrenceId) {
		
		$instance = EventInstance::find(['eventId'=>$this->event->id, 'recurrenceId'=>$recurrenceId])->single();
		if(empty($instance)) { // new override (should not save if not changed)
			$instance = $this->event->createInstance($recurrenceId);
		}
		$this->instance = $instance;
		return $this->instance;
	}

	public function setInstance(EventInstance $instance) {
		$this->instance = $instance;
	}

	public function addAlarms($alarms) {
		$alarms = (array)$alarms;

		foreach($alarms as $alarm) {
			$alarm->addTo($this);
		}
	}

	protected function internalSave() {

		if($this->event->getIsRecurring()) {

			if(isset($this->instance) && !$this->fromHere) {
				$this->instance->applyPatch($this->event);
				return $this->instance->save();
			} else if(!$this->isFirstInSeries()) {
				return $this->saveNewSeries();
			}
			return parent::internalSave();
		} else {
			return parent::internalSave();
		}

		 // save none recurring or complete series
	}

	public function saveFromHere() {
		$this->fromHere = true;
		$success = $this->save();
		$this->fromHere = false;
		return $success;
	}
	
	protected function internalDelete($hard) {
		if($this->event->getIsRecurring()) {
			if(isset($this->instance) && !$this->fromHere) {
				$this->instance->applyException(); // EXDATE
				return $this->instance->save();
			}
			else if(!$this->isFirstInSeries()) {
				$this->recurrenceRule->stopBefore($this->getRecurrenceId());
				return $this->recurrenceRule->save();
			}
		}

		return parent::internalDelete($hard); // delete none recurring or complete series
	}

	public function deleteFromHere() {
		$this->fromHere = true;
		$success = $this->delete();
		$this->fromHere = false;
		return $success;
	}

	protected function isFirstInSeries() {
		if($this->isNew() || empty($this->instance)) {
			return true;
		}
		$startAt = $this->event->isModified('startAt') ? $this->event->getOldAttributeValue('startAt') : $this->event->startAt;
		return ($this->instance->recurrenceId == $startAt);
	}

	/**
	 * Set the until time of this recurrence rule and create a new event
	 * with the same recurring rule starting from The occurrence start time
	 * @todo: if startAt in new series changes the moved exceptions need the same diff
	 */
	private function saveNewSeries() {

		$newSeries = $this->calendar->newEvent();
		$newSeries->eventId = null;
		$newSeries->event = $this->event->cloneMe();
		$newSeries->event->uid = \IFW\Util\UUID::v4();
		$newSeries->event->setStartAt($this->instance->recurrenceId);
		$newSeries->event->setEndAt($this->instance->getEndAt());
		$rrule = $this->recurrenceRule->toArray();
		$newSeries->event->setValues(['recurrenceRule'=>$rrule]);
		$success = $newSeries->save();

		// Reattach instances to new series
		foreach($this->event->instances as $instance) {
			if($instance->recurrenceId < $this->getRecurrenceId()) {
				continue;
			}
			$instance->eventId = $newSeries->id;
			$success = $success && $instance->save();
		}

		return $success && $this->deleteFromHere();

	}
}