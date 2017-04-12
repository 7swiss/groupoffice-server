<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use GO\Core\Orm\Record;
use IFW\Auth\Permissions\CreatorOnly;

/**
 * Calendar holds the calendar-specific information such as name, color and sync info
 * They contain events but these are linked together by @see Attendees
 *
 * @property Group $owner
 * @property Alarm[] $defaultAlarms this alarm is default for all events in this calendar
 * @property Event[] $events Loaded through attendees
 */
class Calendar extends Record {
	
	/**
	 * Primary key auto increment.
	 * @var int
	 */							
	public $id;

	/**
	 * It's name
	 * @var string
	 */							
	public $name;

	/**
	 * default color for the calendar
	 * @var string
	 */							
	public $color;

	/**
	 * Everytime something in this calendar changes the number is incresed
	 * @var int
	 */							
	public $version = 1;

	/**
	 * 
	 * @var int
	 */							
	public $ownedBy;

	//TODO: implement
	const ROLE_NONE = 1;
	const ROLE_FREEBUSY = 2;
	const ROLE_READER = 3;
	const ROLE_WRITER = 4;
	const ROLE_OWNER = 5;

	// DEFINE
	
	public static function tableName() {
		return 'calendar_calendar';
	}

	protected static function internalGetPermissions() {
		return new \GO\Core\Auth\Permissions\Model\GroupPermissions(CalendarGroup::class);
	}
	
	protected static function defineRelations() {
		//TODO: join events to this and select by timespan
		self::hasOne('owner', Group::class, ['ownedBy'=>'id']);
		self::hasMany('groups', CalendarGroup::class, ['id' => 'calendarId']);
		self::hasMany('attendees', Attendee::class, ['id' => 'calendarId']);
		self::hasMany('defaultAlarms', DefaultAlarm::class, ['id' => 'calendarId']);
	}
	
	// ATTRIBUTES
	
	// OPERATIONS
	
	/**
	 * Make the current version higher
	 */
	public function up() {
		$this->version++;
	}

	public function getUri() {
		return $this->name.'-'.$this->id;
	}
	
	/**
	 * places an event inside this calendar
	 * @param Event $event
	 */
	public function add(Event $event) {
		$event->calendarId = $this->id;
		return $event;
	}

	/**
	 * Test
	 * @return Attendee
	 */
	public function newEvent() {
		$attendee = new Attendee();
		$attendee->email = \GO()->getAuth()->user()->email;
		$attendee->groupId = $this->ownedBy;
		$attendee->setCalendar($this);
		$attendee->responseStatus = AttendeeStatus::Accepted;
		$event = new Event();
		$attendee->event = $event;
		$attendee->event->organizerEmail = \GO()->getAuth()->user()->email;
		return $attendee;
	}
}
