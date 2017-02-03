<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Orm\Record;

/**
 * A calendar resource. Like a beamer of a room.
 * Add this to the attendees to keep availability schedual
 *
 * @property int $recurrenceId PK and FK to recurrence
 */
class EventException extends Record {

	/**
	 * PK.
	 * @var int
	 */							
	public $id;

	/**
	 * FK to event this is an exception for
	 * @var int
	 */							
	public $recurrenceEventId;

	/**
	 * the start time of the event the occurence that has the exception
	 * @var \DateTime
	 */							
	public $date;

	public static function tableName() {
		return 'calendar_event_exception';
	}

	protected static function defineRelations() {
		self::hasOne('event', Event::class, ['id' => 'exceptionId']);
		self::hasMany('recurrenceRule', RecurrenceRule::class, ['recurrenceEventId' => 'eventId']);
	}

}
