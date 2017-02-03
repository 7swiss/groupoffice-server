<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Orm\Record;
use IFW\Util\DateTime;
use IFW\Util\UUID;
use IFW\Auth\Permissions\Everyone;

/**
 * This holds information about a single event. The event can occur one-time or
 * can be a recurring event.
 *
 * @property-read string $uri a URI the event can be seen.
 * @property-read bool $isRecurring does this event occurs more then ones
 * @property-read bool $isException is this instance an exception of a recurring event
 * 
 * @property RecurrenceRule $recurrenceRule the rule that describe when an how this event is recurring
 * @property Attendee $organizer the attendee that created the event
 * @property Attendee[] $attendees all attendees that are added including organizer
 * @property EventAttachment[] $attachments File attachments
 */
class Event extends Record {

	/**
	 * Primary key auto increment.
	 * @var int
	 */							
	public $id;

	/**
	 * A universal unique identifier for the object.
	 * @var string
	 */							
	public $uuid;

	/**
	 * This is an revision number that is increased by 1 every time the organizer
	 * makes a significant change
	 * @var int
	 */
	public $sequence = 0;

	/**
	 * Time is ignored for this event when true
	 * @var bool
	 */							
	public $allDay = false;

	/**
	 * The start time of the event
	 * @var DateTime
	 */							
	public $startAt;

	/**
	 * The end time of the event (or the occurence)
	 * @var DateTime
	 */							
	public $endAt;

	/**
	 * The creation time
	 * @var DateTime
	 */							
	public $createdAt;

	/**
	 * The modification time
	 * @var DateTime
	 */							
	public $modifiedAt;

	/**
	 * The title
	 * @var string
	 */							
	protected $title;

	/**
	 * free text that would describe the event
	 * @var string
	 */							
	public $description;

	/**
	 * The location where the vent take place
	 * @var string
	 */							
	public $location;

	/**
	 * This object in VEvent format @see VObject library
	 * @var string
	 */							
	public $vevent;

	/**
	 * Status of event (confirmed, canceled, tentative)
	 * @var int
	 */							
	public $status = 1;

	/**
	 * auto tagging to give the event some flair. See Resource folder
	 * @var string
	 */							
	public $tag;

	/**
	 * PUBLIC, PRIVATE, CONFIDENTIAL
	 * @var int
	 */							
	public $visibility = 1;

	public $busy = 1;

	/**
	 * soft delete an event
	 * @var Datetime
	 */							
	public $deletedAt = null;

	/**
	 * 
	 * @var string
	 */							
	public $organizerEmail;

	// DEFINE
	private $oldVEvent = null;

	/**
	 * The Date(time) that an instance of a recurring series is occurring
	 * @var \DateTime
	 */
	protected $recurrenceId = null;
	private $singleInstance = null;

	/**
	 * The exception object that is applied to this instance
	 * @var RecurrenceException
	 */
	private $exception = null;

	protected function init() {
		if ($this->isNew()) {
			$this->uuid = UUID::v4();
			$this->setStartTime($this->newStartTime());
		}
	}

	protected static function internalGetPermissions() {
		return new Everyone();
	}

	private function newStartTime() {
		$datetime = new DateTime();
		$second = $datetime->format("s");
		$datetime->add(new \DateInterval("PT".(60-$second)."S"));
		$minute = $datetime->format("i") % 10;
		$diff = 10 - $minute;
		$datetime->add(new \DateInterval("PT".$diff."M"));
		return $datetime;
	}

	public static function tableName() {
		return 'calendar_event';
	}

	/**
	 * TODO: function is not called when the attributes of events are set relational
	 * Eg. through an attendance
	 * @param string $value Title of event
	 */
	public function setTitle($value) {
		$tags = require(dirname(__FILE__) . '/../Resources/tags/nl.php'); //<-- todo: use users language
		$this->tag = null;
		foreach($tags as $tag => $possibleMatches) {
			foreach($possibleMatches as $possibleMatch) {
				if (stripos($value, $possibleMatch) !== false) {
					$this->tag = $tag;
					break 2;
				}
			}
		}
		$this->title = $value;
	}
	
	public function getTitle() {
		return $this->title;
	}

	static public function findByUUID($uuid) {
		return self::find(['uuid'=>$uuid, 'exceptionId'=>null])->single();
	}

	static public function findRecurring($start, $end) {
		$query = new \IFW\Orm\Query();
		$query->joinRelation('recurrenceRule');
		$events = self::find($query);
		$allOccurrences = [];
		foreach($events as $event) {
			$allOccurrences = array_merge($allOccurrences, $event->recurrenceRule->getOccurences($start, $end));
		}
		return new \IFW\Data\Store($allOccurrences);
	}
	
	protected static function defineRelations() {
		self::hasOne('recurrenceRule', RecurrenceRule::class, ['id' => 'eventId']);
		self::hasMany('attendees', Attendee::class, ['id' => 'eventId']);
		self::hasMany('attachments', EventAttachment::class, ['id' => 'eventId']);
		self::hasOne('calendar', Calendar::class, ['id'=>'eventId'])
						->via(Attendee::class,['calendarId'=>'id']);
	}

	// ATTRIBUTES
	
	public function getIsRecurring() {
		return !empty($this->recurrenceRule) && !$this->recurrenceRule->isNew() && !empty($this->recurrenceRule->frequency);
	}

	/**
	 * Returns true when more attendees then just yourself
	 * @return boolean
	 */
	public function getHasAttendees() {
		$count = 0;
		foreach($this->attendees as $attendee) {
			$count++;
			if($count > 1)
				return true;
		}
		return false;
	}

	public function applyException(RecurrenceException $exception) {
		$this->exception = $exception;
		foreach($exception->getColumns() as $colName => $name) {
			if($exception->{$colName} !== null && !in_array($colName, ['id', 'eventId', 'isRemoved', 'classification'])) {
				$this->{$colName} = $exception->{$colName};
			}
		}
		$this->clearModified(); //
	}

	public function getIsException() {
		return !empty($this->exception);
	}

	/**
	 * Set the date of the startAt attribute
	 * @param string $date in Y-m-d format
	 */
	public function setStartDate($date) {
		$arr = explode('-', $date);
		$this->startAt->setDate($arr[0], $arr[1], $arr[2]);
	}

	/**
	 * Set the date of the endAt attribute
	 * @param string $date in Y-m-d format
	 */
	public function setEndDate($date) {
		$arr = explode('-', $date);
		$this->endAt->setDate($arr[0], $arr[1], $arr[2]);
	}

	/**
	 * When this event is an exception for another event.
	 * This is the date for the occurrence
	 */
	public function getRecurrenceId() {
		return $this->recurrenceId; // or start time?
	}

	/**
	 * Will move the endTime with it and set the occurrence ID
	 * @param Datetime $recurrenceId
	 * this instance forward. When true we create an exception
	 */
	public function addRecurrenceId($recurrenceId) {

		$this->recurrenceId = $recurrenceId;

		//Apply exception if any
		$exception = RecurrenceException::find([
			 'eventId' => $this->id,
			 'recurrenceId' => $this->recurrenceId
		])->single();
		if(!empty($exception)) {
			$this->applyException($exception);
		}

		if(!empty($recurrenceId) && $recurrenceId !== $this->startAt) {
			$this->setStartTime($recurrenceId);
		}
	}

	private function setStartTime($startTime) {
		$diff = $this->getDuration();
		$this->startAt = clone $startTime;
		$this->endAt = clone $startTime;
		$this->endAt->add($diff);
	}

	public function setSingleInstance($value) {
		$this->singleInstance = $value;
	}

	protected function isFirstInSeries() {
		$startAt = $this->isModified('startAt') ? $this->getOldAttributeValue('startAt') : $this->startAt;
		return $this->isNew() || ($this->recurrenceId == $startAt);
	}

	/**
	 * When the recurrenceId of an event was set it represents a single instance
	 * of a recurring event. When editing we create an exception
	 * @return bool
	 */
	protected function isInstance() {
		return $this->getIsException() || ($this->singleInstance && !empty($this->recurrenceId));
	}

	private function getDuration() {
		if(empty($this->endAt)){
			return new \DateInterval('PT1H');
		}
		return $this->startAt->diff($this->endAt);
	}

	// OVERRIDES

	protected function internalDelete($hard) {

		if($this->isRecurring) {
			if($this->isInstance()) {
				return $this->saveException($this->recurrenceId);
			} else if(!$this->isFirstInSeries()) {
				return $this->deleteFromHere(); // close serie
			}
		}

		return parent::internalDelete($hard);
	}

	private function deleteFromHere() {

		$this->recurrenceRule->stopBefore($this->recurrenceId);
		return $this->recurrenceRule->save();
	}

	protected function internalSave() {

		if($this->getIsRecurring()) {
			if($this->isInstance()) {
				return $this->saveException($this->recurrenceId, $this);
			} else if (!$this->isFirstInSeries()) {
				return $this->saveFromHere($this->startAt);
			}
		}

		if(!$this->getIsException() && !$this->isModified('vevent')) {
			// THIS GIVES A SEGMENTATION FAULT IN PHP 7.0.13
			$this->vevent = ICalendarHelper::toVObject($this)->serialize();
		}

		if($this->getHasAttendees()) {
			// This must be before save but after vevent is set
			$this->notify();
		}

		return parent::internalSave();

	}

	protected function internalValidate() {

		$success = parent::internalValidate();

		if($this->startAt > $this->endAt) {
			$this->setValidationError('startAt', 'compareGreaterThen');
			$success = false;
		}
		return $success;
	}

	// OPERATIONS
	
	/**
	 * Confirm that the event is really happening
	 */
	public function confirm() {
		$this->status = EventStatus::Confirmed;
		return $this;
	}

	private function cloneMe() {
		$event = new self();
		$properties = $this->toArray();
		//unset($properties['recurrenceId']);
		$event->setValues($properties);
		$event->id = null;
		$event->uuid = UUID::v4();

		foreach($this->attendees as $attendee) {
			$clone = new Attendee();
			$clone->setValues($attendee->toArray());
			$clone->eventId = null;
			$event->attendees[] = $clone;
			// Maybe clone alarms to?
		}
		foreach($this->attachments as $attachment) {
			$clone = new EventAttachment();
			$clone->setValues($attachment->toArray());
			$clone->eventId = null;
			$event->attachments[] = $clone;
		}
		//Todo: clone Resources when they are implemented
		return $event;
	}

	/**
	 * Set the until time of this recurrence rule and create a new event
	 * with the same recurring rule starting from The occurrence start time
	 */
	private function saveFromHere(DateTime $occurrence) {
	
		$newSeries = $this->cloneMe();
		$newSeries->startAt = clone $occurrence;

		$rrule = new RecurrenceRule();
		$rrule->setValues($this->recurrenceRule->toArray());
		$rrule->eventId = null;
		$newSeries->recurrenceRule = $rrule;
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
		$this->exception->eventId = $this->id;
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

	/**
	 * Cancel the event
	 * @return \GO\Modules\GroupOffice\Calendar\Model\Event
	 */
	public function cancel() {
		$this->status = EventStatus::Cancelled;
		return $this;
	}

	/**
	 * Will notify the attendee as well as the organizer
	 * ITIP will find out who you are
	 */
	public function notify() {
		ICalendarHelper::sendItip($this);
	}
	
	public function inTimeRange($from, $till) {
		return ($this->startAt < $till && $this->endAt > $from);
	}
}
