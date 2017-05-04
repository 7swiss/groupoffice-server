<?php

/**
 * Group-Office
 *
 * Copyright Intermesh BV.
 * This file is part of Group-Office. You should have received a copy of the
 * Group-Office license along with Group-Office. See the file /LICENSE.TXT
 *
 * If you have questions write an e-mail to info@intermesh.nl
 *
 * @todo create mapping and make independent of DAV
 */

namespace GO\Modules\GroupOffice\Dav\Model;

use GO\Modules\GroupOffice\Calendar\Model\ICalendarHelper;
use GO\Modules\GroupOffice\Contacts\Model\CalendarEvent;
use IFW\Auth\Permissions\ViaRelation;
//use IFW\Orm\Record;
use IFW\Orm\PropertyRecord;
use Sabre\VObject\Reader;

/**
 * The Card model
 *
 */
class Event extends PropertyRecord {

	/**
	 *
	 * @var int
	 */
	public $eventId;

	/**
	 *
	 * @var int
	 */
	public $calendarId;

	/**
	 *
	 * @var \DateTime
	 */
	public $modifiedAt;

	/**
	 *
	 * @var string
	 */
	public $data;

	/**
	 * Size of the data in bytes
	 * @var int
	 */
	public $size;

	/**
	 *
	 * @var string
	 */
	public $uri;

	/**
	 *
	 * @var string
	 */
	public $etag;

	const EXTENSION = 'ics';

	protected static function internalGetPermissions() {
		return new ViaRelation('calendarEvent');
	}

	protected static function defineRelations() {
		self::hasOne('calendarEvent', CalendarEvent::class, ['eventId' => 'eventId', 'calendarId' => 'calendarId']);
	}


	protected function internalSave() {

		if($this->isModified('data') && \GO()->getModules()->has('GO\Modules\GroupOffice\Calendar\Module')) {

			$event = \GO\Modules\GroupOffice\Calendar\Model\ICalendarHelper::fromVObject($this->vevent(), $this->calendarId);
			//$event->calendarId = $this->calendarId;
			//$event->groupId = $this->groupId;
			$event->modifiedAt = $this->modifiedAt = new \DateTime();
			if($event->getHasAttendees()) {
				// Will notify the attendee as well as the organizer
				// ITIP will find out who you are
				\GO\Modules\GroupOffice\Calendar\Model\ICalendarHelper::sendItip($this);
			}
		}

		return parent::internalSave();
	}

	private function vevent() {
		if(empty($this->data)) {
			return null;
		}
		return Reader::read($this->data, Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
	}

	/**
	 * Called in CalendarEvent::BEFORE_SAVE event
	 * @param \GO\Modules\GroupOffice\Calendar\Model\CalendarEvent $calendarEvent
	 */
	static public function onEventChange($calendarEvent) {
		$vcalendar = self::find([
			'calendarId' => $calendarEvent->calendarId,
			'eventId' => $calendarEvent->eventId
		])->single();
		if(empty($vcalendar)) {
			$vcalendar = new self();
		}
		if($vcalendar->isNew() || $calendarEvent->event->modifiedAt > $vcalendar->modifiedAt) {
			//update vevent
			$vcalendar->data = ICalendarHelper::toVObject($calendarEvent, $this->vevent())->serialize();
			$vcalendar->size = strlen($this->data);
			$vcalendar->eventId = $calendarEvent->eventId;
			$vcalendar->calendarId = $calendarEvent->calendarId;
			$vcalendar->uid = $calendarEvent->event->uuid;
			$vcalendar->modifiedAt = $calendarEvent->event->modifiedAt;
			$vcalendar->save();
		}
	}

}
