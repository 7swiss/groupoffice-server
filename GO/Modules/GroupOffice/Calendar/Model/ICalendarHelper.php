<?php
/**
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Calendar\Model;

use Sabre\VObject;
use GO\Core\Email\Model\Message;
use GO\Modules\GroupOffice\Calendar\Model\Event;
use Sabre\VObject\Recur\RRuleIterator;

class ICalendarHelper {

	/**
	 * Parse an Event object to a VObject
	 * @param \GO\Modules\GroupOffice\Calendar\Model\Event $event
	 */
	static public function toVObject(Event $event) {

		$vcalendar = new VObject\Component\VCalendar([
			'VEVENT' => [
				'UID' => $event->uuid,
				'SUMMARY' => $event->title,
				'STATUS' => EventStatus::$text[$event->status],
				'LAST-MODIFIED' => $event->modifiedAt, // @todo: check if datetime must be UTC
				'DTSTAMP' => $event->createdAt,
				'DTSTART' => $event->startAt,
				'DTEND' => $event->endAt,
			]
		]);
		if($event->allDay) {
			$vcalendar->VEVENT->DTSTART['VALUE'] = 'DATE';
			$vcalendar->VEVENT->DTEND['VALUE'] = 'DATE';
		}

		// Sequence is for updates on the event its used for ITIP
		!isset($event->sequence) ?: $vcalendar->VEVENT->SEQUENCE = $event->sequence; // @todo implement in Event
		empty($event->description) ?: $vcalendar->VEVENT->DESCRIPTION = $event->description;
		empty($event->location) ?: $vcalendar->VEVENT->LOCATION = $event->location;
		empty($event->tag) ?: $vcalendar->VEVENT->CATEGORIES = $event->tag;
		($event->visibility === 2) ?: $vcalendar->VEVENT->CLASS = Visibility::$text[$event->visibility];

		if($event->getIsRecurring()) {
			$vcalendar->VEVENT->RRULE = self::createRrule($event->recurrenceRule);
			foreach($event->recurrenceRule->exceptions as $exception) {
				if($exception->isRemoved) {
				$vcalendar->VEVENT->add(
					'EXDATE',
					$exception->recurrenceId
					//['VALUE' => $event->allDay ? "DATE" : "DATETIME"]
				);
				} else {
					// TODO add exception event with RecurrenceId to VCalendar object
//					$vcalendar->VEVENT->add(
//						'RECURRENCE-ID',
//						$event->exception->recurrenceId,
//						['VALUE'=>$event->allDay ? "DATE" : "DATETIME"]
//					);
				}
			}
		}

		// FROM GO 6.1 Comments:
		// If this is a meeting REQUEST then we must send all participants.
		// For a CANCEL or REPLY we must send the organizer and the current user.
		foreach($event->attendees as $attendee) {
			$attr = ['cn' => $attendee->getName()];
			($attendee->responseStatus === AttendeeStatus::__default) ?: $attr['partstat'] = AttendeeStatus::$text[$attendee->responseStatus];
			($attendee->role === Role::__default) ?: $attr['role'] = Role::$text[$attendee->role];

//			var_dump($attendee);
			$type = $event->organizerEmail == $attendee->email ? 'ORGANIZER' : 'ATTENDEE';
//			$type = $attendee->getIsOrganizer() ? 'ORGANIZER' : 'ATTENDEE';
//			var_dump($type);

			$vcalendar->VEVENT->add(
				$type,
				$attendee->email, $attr
			);
		}

		//@todo: VALARMS depend on who fetched the event. Need to be implemented when caldav is build
		//@todo: add Files
		return $vcalendar;
	}

	/**
	 * Parse a VObject to an Event object
	 * @param VObject\Component\VCalendar $calendar
	 * @return Event
	 */
	static public function fromVObject(VObject\Component\VCalendar $calendar) {

		$events = [];

		foreach($calendar->VEVENT as $vevent) {

			$event = new Event();
			$event->uuid = (string)$vevent->UID;
			$event->createdAt = $vevent->DTSTAMP->getDateTime();

			$event->modifiedAt = $vevent->{'LAST-MODIFIED'}->getDateTime();
			$event->startAt = $vevent->DTSTART->getDateTime();
			$event->endAt = $vevent->DTEND->getDateTime();
			$event->allDay = !$vevent->DTSTART->hasTime();
			$event->status = EventStatus::fromText($vevent->STATUS);
			$event->title = (string)$vevent->SUMMARY;
			$event->description = (string)$vevent->DESCRIPTION;
			$event->location = (string)$vevent->LOCATION;
			$event->vevent = $calendar->serialize();
			//$event->sequence = $vevent->SEQUENCE; // TODO
			$event->visibility = Visibility::fromText($vevent->CLASS);

			$event->organizerEmail = str_replace('mailto:', '',(string)$vevent->ORGANIZER);
			$organizer = new Attendee();
			$organizer->email = str_replace('mailto:', '',(string)$vevent->ORGANIZER);
			$organizer->responseStatus = AttendeeStatus::fromText($vevent->ORGANIZER['PARTSTAT']);
			$organizer->role = Role::fromText($vevent->ORGANIZER['ROLE']);
			$event->attendees[] = $organizer;

			foreach($vevent->ATTENDEE as $vattendee) {
				$attendee = new Attendee();
				//$attendee->name = $vattendee['CN'];
				$attendee->email = str_replace('mailto:', '',(string)$vattendee); // Will link to userId when found
				$attendee->responseStatus = AttendeeStatus::fromText($vattendee['PARTSTAT']);
				$attendee->role = Role::fromText($vattendee['ROLE']);
				$event->attendees[] = $attendee;
			}
			//TODO VALARM for (attendee specific)
			if(!empty((string)$vevent->RRULE)) {
				$event->recurrenceRule = self::rruleToObject($vevent->RRULE);
			}
			//TODO RECURRENCE-ID and EXDATE

			$events[] = $event;
		}
		return $events;
	}

	static public function makeRecurrenceIterator(RecurrenceRule $rule) {
		$values = ['FREQ' => $rule->frequency];
		empty($rule->occurrences) ?: $values['COUNT'] = $rule->occurrences;
		empty($rule->until) ?:	$values['UNTIL'] = $rule->until->format('Ymd');
		empty($rule->interval) ?: $values['INTERVAL'] = $rule->interval;
		empty($rule->bySetPos) ?: $values['BYSETPOS'] = $rule->bySetPos;
		empty($rule->bySecond) ?: $values['BYSECOND'] = $rule->bySecond;
		empty($rule->byMinute) ?: $values['BYMINUTE'] = $rule->byMinute;
		empty($rule->byHour) ?: $values['BYHOUR'] = $rule->byHour;
		empty($rule->byDay) ?: $values['BYDAY'] = $rule->byDay;
		empty($rule->byMonthday) ?: $values['BYMONTHDAY'] = $rule->byMonthday;
		empty($rule->byMonth) ?: $values['BYMONTH'] = $rule->byMonth;
		return new RRuleIterator($values, $rule->event->startAt);
	}
	
	static private $ruleMap = [
			'FREQ' => 'frequency',
			'COUNT' => 'occurrences',
			'UNTIL' => 'until',
			'INTERVAL' => 'interval',
			'BYSETPOS' => 'bySetPos',
			'BYSECOND' => 'bySecond',
			'BYMINUTE' => 'byMinute',
			'BYHOUR' => 'byHour',
			'BYDAY' => 'byDay',
			'BYMONTHDAY' => 'byMonthday',
			'BYMONTH' => 'byMonth',
		];

	/**
	 * Create an iCalendar RRule from a RecurrenceRule object
	 * @param RecurrenceRule $recurrenceRule
	 * @return \Sabre\VObject\Property\ICalendar\Recur $rule
	 */
	static private function createRrule($recurrenceRule) {
		$rule = '';
		foreach(self::$ruleMap as $key => $value) {
			if(!empty($recurrenceRule->{$value})) {
				$rule .= $key . '=';
				$rule .= ($value == 'until') ? $recurrenceRule->{$value}->format('Ymd') : $recurrenceRule->{$value};
				$rule .= ';';
			}
		}
		return $rule;
	}

	
	static private function rruleToObject(VObject\Property\ICalendar\Recur $rule) {
		
		$recurrenceRule = new RecurrenceRule();
		foreach($rule as $key => $value) {
			$mappedKey = self::$ruleMap[$key];
			$recurrenceRule{$mappedKey} = $value;
		}
		return $recurrenceRule;
	}

	/**
	 * Read the VObject string data and return an Event object
	 * If a Blob object is passed and the mimeType is text/calendar teh contact will be fetched
	 * 
	 * @param string|Blob $data VObject data
	 * @return VObject\Document
	 */
	static public function read($data) {
		if($data instanceof \GO\Core\Blob\Model\Blob && $data->contentType === 'text/calendar') {
			$data = file_get_contents($data->getPath());
		}
		return VObject\Reader::read(\IFW\Util\StringUtil::cleanUtf8($data), VObject\Reader::OPTION_FORGIVING);		
	}

	/**
	 * Sending invite, cancel, reply update via email
	 *
	 * @param Event $event the new, deleted or modified event
	 * @param string $fromEmail Sending on behalf of
	 */
	static public function sendItip(Event $event, $status = null) {
		$oldCalendar = null;
		$calendar = null;
		if(!$event->isNew()) {
			$oldAttributes = $event->getModifiedAttributes();
			$oldCalendar = $oldAttributes['vevent'];
		}
		if(!$event->markDeleted) {
			$calendar = $event->vevent;
			if($status !== null) {
				$calendar->VEVENT->ATTENDEE['PARTSTAT'] = $status;
			}
		}
		

		$broker = new VObject\ITip\Broker();
		$messages = $broker->parseEvent(
			$calendar,
			\GO()->getAuth()->user()->email,
			$oldCalendar
		);
	
		//\GO()->debug(\GO()->getAuth()->user()->email);
		
		foreach($messages as $message) {
			self::sendMail($message);
		}
	}

	/**
	 * Just send the RSVP mail (used in Messages module)
	 * @param type $vcal
	 * @param type $status
	 */
	static public function rsvp($vcal, $status, $currentUser) {

		$broker = new VObject\ITip\Broker();

		$old = clone $vcal;

		//$vcal->VEVENT->ATTENDEE['RSVP'] = 'TRUE';
		$vcal->VEVENT->ATTENDEE['PARTSTAT'] = $status;

		$messages = $broker->parseEvent(
			$vcal,
			$currentUser, //'michael@intermesh.dev',//\GO()->getAuth()->user()->email,
			$old
		);

		foreach($messages as $message) {
			echo $message->message->serialize();
			//self::sendMail($message);
		}

	}

//	static protected function createItipMessage($vcal) { // MOVe to RSVP
//		$message = new \Sabre\VObject\ITip\Message();
//		$message->message = $vcal;
//		$message->method = 'REQUEST';
//		$message->component = 'VEVENT';
//		$message->uid = $vcal->VEVENT->UID;
//		$message->sequence = isset($vcal->VEVENT[0]) ? (string)$vcal->VEVENT[0]->SEQUENCE : null;
//
//		$message->sender = $vcal->VEVENT->ATTENDEE->getValue();
//		$message->senderName = isset($vcal->VEVENT->ATTENDEE['CN']) ? $vcal->VEVENT->ATTENDEE['CN']->getValue() : null;
//		$message->recipient = $vcal->VEVENT->ORGANIZER->getValue();
//		$message->recipientName = isset($vcal->VEVENT->ORGANIZER['CN']) ? $vcal->VEVENT->ORGANIZER['CN'] : null;
//
//		return $message;
//	}

	/**
	 * Use Swift to send the ITIP message
	 * @param VObject\ITip\Message $itip
	 */
	static public function sendMail(VObject\ITip\Message $itip) {

		$invite = new \Swift_Attachment($itip->message->serialize(), 'invite.ics','text/calendar');
		\GO()->getMailer()->compose()
			->setSubject($itip->message->VEVENT->SUMMARY)
			->setFrom($itip->sender, (string)$itip->senderName)
			->setTo($itip->recipient, (string)$itip->recipientName)
			->attach($invite)
			->setBody('tada')
			->send();
	}

	/**
	 * Process an incomming Email VEVENT with ITIP
	 *
	 * @param Message $message the message containing ITIP information
	 */
	static public function processItip($message) {

		$oldCal = null; //TodoL check if invite was not new

		$broker = new VObject\ITip\Broker();
		return $broker->processMessage($message, $oldCal);

	}

}