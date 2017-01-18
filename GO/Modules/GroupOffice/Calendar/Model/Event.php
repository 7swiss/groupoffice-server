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
 * @property-read bool $isException is the instance an expection of a recurring event 
 * @property Datetime $recurranceId The start time of the occurence of a recurring event this instance is an exception of
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
	 * true when the event lasts for the entier day
	 * @var bool
	 */							
	public $allDay = false;

	/**
	 * The start time of the event
	 * @var \DateTime
	 */							
	public $startAt;

	/**
	 * The end time of the event (or the occurence)
	 * @var \DateTime
	 */							
	public $endAt;

	/**
	 * The creation time
	 * @var \DateTime
	 */							
	public $createdAt;

	/**
	 * The modification time
	 * @var \DateTime
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
	 * Status of event (confirmed, cancelled, tentative)
	 * @var int
	 */							
	public $status = 1;

	/**
	 * auto taggen to give the event some flair. See Resource folder
	 * @var string
	 */							
	public $tag;

	/**
	 * PUBLIC, PRIVATE, CONFIDENTIAL
	 * @var int
	 */							
	public $visibility = 1;

	/**
	 * soft delete an event
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * 
	 * @var int
	 */							
	public $exceptionId;

	/**
	 * 
	 * @var string
	 */							
	public $organizerEmail;

	// DEFINE
	private $oldVEvent = null;

	private $changeOccurences;

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
		$datetime = new \DateTime();
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
	 * When changing an recurring event:
	 * '1' for single occurrence. anything else for 'from this occurrence forward'
	 * @param int $value 
	 */
	public function setChangeOccurrence($value) {
		if(!$this->isRecurring) {
			throw \Exception('You can only make exceptions for recurring events');
		}
		$this->changeOccurences = $value;
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
		self::hasOne('exception', EventException::class, ['id' => 'recurrenceEventId']);
		self::hasMany('attendees', Attendee::class, ['id' => 'eventId']);
		self::hasMany('attachments', EventAttachment::class, ['id' => 'eventId']);
		self::hasOne('calendar', Calendar::class, ['id'=>'eventId'])
						->via(Attendee::class,['calendarId'=>'id']);
	}

	// ATTRIBUTES
	
	public function getIsRecurring() {
		return !empty($this->recurrenceRule);
	}

	public function getHasAttendees() {
		$count = 0;
		foreach($this->attendees as $attendee) {
			$count++;
			if($count > 1)
				return true;
		}
		return false;
	}
	
	public function getIsException() {
		return !empty($this->recurrenceId) && !empty($this->exception);
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
	public function getOccurrenceData() {

		return empty($this->exception) ? null : $this->exception->date;
	}

	/**
	 * Will move the endTime with it
	 * @param Datetime $datetime
	 */
	public function setStartTime(DateTime $datetime) {
		$diff = $this->getDuration();
		$this->startAt = clone $datetime;
		$this->endAt = $datetime->add($diff);
	}

	private function getDuration() {
		if(empty($this->endAt)){
			return new \DateInterval('PT1H');
		}
		return $this->startAt->diff($this->endAt);
	}

	public function internalSave() {

		if(isset($this->changeOccurences)) {
			if($this->changeOccurences === 1) {
				$this->saveChangesAsException($this->recurranceId);
			} else {
				$this->saveChangesFromHere($this->recurranceId);
			}
		}

		if(!$this->isModified('vevent')) {
			// THIS GIVES A SEGMENTATION FAULT IN PHP 7.0.13
			//$this->vevent = ICalendarHelper::toVObject($this)->serialize();
		}

		if($this->getHasAttendees()) {
			// This must be before save but after vevent is set
			$this->notify();
		}

		return parent::internalSave();

	}

	// OVERRIDES

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

	/**
	 * Set the until time of this recurrence rule and create a new event
	 * with the same recurring rule starting from The occurrence start time
	 */
	private function saveChangesFromHere(DateTime $occurrence) {
		//need occurrence start time

		$newSeries = new self();
		$newSeries->setValues($this->toArray());
		$newSeries->uuid = UUID::v4();
		//Todo: Copy attendees, rrule, attachment. Move Exceptions after $occurrence

		$newSeries->recurrenceRule->startAt = $occurrence; //occurrence that we are editing
		$this->recurrenceRule->until = $occurrence; //occurrence that we are editing

	}

	private function saveChangesAsException(DateTime $occurrence) {
		$event = new self();
		$event->setValues($this->toArray());
		$event->uuid = UUID::v4();

		$this->addException($occurrence, $event);
		return $event->save();
	}

	public function addException(DateTime $occurrence, Event &$replacementEvent = null) {
		$exception = new EventException();
		$exception->date = $occurrence;
		$this->recurrenceRule->exceptions[] = $exception;
		$success = $exception->save();
		$replacementEvent->exceptionId = $exception->id;
		return $success;
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
