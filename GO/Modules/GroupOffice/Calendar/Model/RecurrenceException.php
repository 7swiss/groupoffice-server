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
 * @property Event $event The event the rule is applied to
 * @property RecurrenceRule $recurrenceRule the rule this is an exceptions for
 */
class RecurrenceException extends Record {

	/**
	 * FK to event this is an exception for
	 * @var int
	 */
	public $eventId;

	/**
	 * The Date(time) that the original event would have occurred
	 * @var \DateTime
	 */
	public $at;


	public static function tableName() {
		return 'calendar_recurrence_exception';
	}

	public static function getPrimaryKey() {
		return ['eventId', 'at'];
	}

	protected static function defineRelations() {
		self::hasOne('recurrenceRule', RecurrenceRule::class, ['eventId' => 'eventId']);
	}

	public function toVEVENT() {
		$props = [
			'RECURRENCE-ID' => $this->at,
			'UID' => $this->event->uid
		];
		empty($this->startAt) ?: $props['DTSTART'] = $this->startAt;
		empty($this->endAt) ?: $props['DTEND'] = $this->endAt;
		empty($this->title) ?: $props['TITLE'] = $this->title;
		empty($this->description) ?: $props['SUMMARY'] = $this->description;
		empty($this->location) ?: $props['LOCATION'] = $this->location;
		empty($this->status) ?: $props['STATUS'] = EventStatus::$text[$this->status];
		empty($this->classification) ?: $props['CLASS'] = Visibility::$text[$this->classification];
	}

}
