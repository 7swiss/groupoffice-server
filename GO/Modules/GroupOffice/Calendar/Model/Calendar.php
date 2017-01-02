<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Orm\Record;
use IFW\Auth\Permissions\OwnerOnly;

/**
 * Calendar holds the calendar-specific information such as name, color and sync info
 * They contain events but these are linked together by @see Attendees
 *
 * @property User $owner
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
		$p = new OwnerOnly();
		$p->userIdField = 'ownedBy';
		return $p;
	}	
	
	protected static function defineRelations() {
		//TODO: join events to this and select by timespan
		self::hasMany('attendees', Attendee::class, ['id' => 'calendarId']);
		self::hasMany('defaultAlarms', DefaultAlarm::class, ['id' => 'calendarId']);
	}
	
	public function internalSave() {
		//$this->update();
		return parent::internalSave();
	}
	
	// ATTRIBUTES
	
	// OPERATIONS
	
	/**
	 * Make the current version higher
	 */
	public function up() {
		$this->version++;
	}
	
	/**
	 * places an event inside this calendar
	 * @param Event $event
	 */
	public function add(Event $event) {
		$event->calendarId = $this->id;
		return $event;
	}
}
