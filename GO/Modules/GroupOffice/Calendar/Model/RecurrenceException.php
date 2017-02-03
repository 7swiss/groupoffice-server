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
class RecurrenceException extends Record {

	/**
	 * PK.
	 * @var int
	 */
	public $id;

	/**
	 * FK to event this is an exception for
	 * @var int
	 */
	public $eventId;

	/**
	 * The Date(time) that the original event would have occurred
	 * @var \DateTime
	 */
	public $recurrenceId;

	/**
	 * When this exception is a removed occurrence
	 * @var bool
	 */
	public $isRemoved;

	// Override attributes for single occurrence
	public $title;
	public $startAt;
	public $endAt;
	public $description;
	public $location;
	public $status;
	public $classification;
	public $busy;

	public static function tableName() {
		return 'calendar_recurrence_exception';
	}

	protected static function defineRelations() {
		self::hasMany('recurrenceRule', RecurrenceRule::class, ['eventId' => 'eventId']);
	}

	static function validAttrs() {
		return [
			 'title', 'startAt', 'endAt', 'description',
			 'location', 'status', 'classification', 'busy'
		];
	}

}
