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
	 * A unique identifier for the object.
	 * @var string
	 */							
	public $uid;

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

	/**
	 * Is event Transparent or Opaque
	 * @var boolean
	 */
	public $busy = true;

	/**
	 * 
	 * @var string
	 */							
	public $organizerEmail;

	/**
	 * The exception object that is applied to this instance
	 * @var RecurrenceException
	 */
	public $recurrenceId = null;

	protected function init() {
		if ($this->isNew()) {
			$this->uid = UUID::v4();
			$diff = $this->getDuration();
			$this->startAt = clone $this->newStartTime();
			$this->endAt = clone $this->startAt;
			$this->endAt->add($diff);
		}
	}

	protected function ignoreOnException() {
		return ['uid', 'organizerEmail', 'allDay','recurrence', 'links'];
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

	public function getTitle() {
		return $this->title;
	}
	/**
	 * Set the tag property when the title contains a certain word
	 * @todo function is not called when the attributes of events are set relational
	 * @param string $value Title of event
	 */
	public function setTitle($value) {
		$tags = require(dirname(__FILE__) . '/../Resources/tags/nl.php'); //<-- @todo: use users language
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
	
	

	static public function findByUID($uid) {
		return self::find(['uid'=>$uid])->single();
	}
	
	protected static function defineRelations() {
		self::hasOne('recurrenceRule', RecurrenceRule::class, ['id' => 'eventId']);
		self::hasMany('attendees', Attendee::class, ['id' => 'eventId']);
		self::hasMany('attachments', EventAttachment::class, ['id' => 'eventId']);
	}

	// ATTRIBUTES
	
	public function getIsRecurring() {
		return !empty($this->recurrenceRule) && !empty($this->recurrenceRule->frequency);
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

	public function getIsException() {
		return $this->recurrenceId !== null;
	}

	public function getDuration() {
		if(empty($this->endAt)){
			return new \DateInterval('PT1H');
		}
		return $this->startAt->diff($this->endAt);
	}

	// OVERRIDES

	protected function internalValidate() {

		$success = parent::internalValidate();

		if($this->startAt > $this->endAt) {
			$this->setValidationError('startAt', \IFW\Validate\ErrorCode::MALFORMED, 'Start date is greater than end date');
			$success = false;
		}
		return $success;
	}

	// OPERATIONS

	public function applyExceptionTODO(RecurrenceException $exception) {
		$this->exception = $exception;
		foreach($exception->getTable()->getColumns() as $colName => $name) {
			if($exception->{$colName} !== null && !in_array($colName, ['id', 'eventId', 'isRemoved', 'recurrenceId'])) {
				$this->{$colName} = $exception->{$colName};
			}
		}
	}

	/**
	 * Confirm that the event is really happening
	 */
	public function confirm() {
		$this->status = EventStatus::Confirmed;
		return $this;
	}

	public function cloneMe() {
		$event = new self();
		$properties = $this->toArray();
		//unset($properties['recurrenceId']);
		$event->setValues($properties);
		$event->id = null;
		$event->uid = UUID::v4();

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
	 * Cancel the event
	 * @return \GO\Modules\GroupOffice\Calendar\Model\Event
	 */
	public function cancel() {
		$this->status = EventStatus::Cancelled;
		return $this;
	}
	
	public function inTimeRange($from, $till) {
		return ($this->startAt < $till && $this->endAt > $from);
	}
}
