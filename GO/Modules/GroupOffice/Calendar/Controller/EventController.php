<?php

namespace GO\Modules\GroupOffice\Calendar\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Calendar\Model\Attendee;
use GO\Modules\GroupOffice\Calendar\Model\Event;
use GO\Modules\GroupOffice\Calendar\Model\CalendarEvent;
use GO\Modules\GroupOffice\Calendar\Model\Calendar;
use IFW;
use IFW\Exception\NotFound;
use IFW\Util\DateTime;
use IFW\Orm\Query;

/**
 * The controller for events
 * 
 * See {@see Event} model for the available properties
 * 
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class EventController extends Controller {

	/**
	 * Fetch events
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param string $from Y-m-d end first event show
	 * @param string $until Y-m-d start last event
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function actionStore($orderColumn = 'event.startAt', $orderDirection = 'ASC', $from = false, $until = false, $searchQuery = "", $returnProperties = "*, event", $where = null) {

		$query = (new Query)
			//->join(Attendee::tableName(), 'a', 't.id = a.eventId', 'LEFT')
			->joinRelation('recurrenceRule', false, 'LEFT')
//			->select('t.*, a.calendarId, a.groupId, a.responseStatus')
			->orderBy([$orderColumn => $orderDirection]);
		
		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['event.title']);
			$recurringEvents = new IFW\Data\Store([]); // does not find recurring events
		} else {
			$query->where('event.startAt <= :until AND event.endAt > :from')
					->bind([':until' => $until, ':from' => $from]);
			$recurringEvents = CalendarEvent::findRecurring(new DateTime($from), new DateTime($until));
			$recurringEvents->setReturnProperties($returnProperties);
		}

		$query->andWhere('recurrenceRule.frequency IS NULL');

		if (!empty($where)) {

			$where = json_decode($where, true);

			if (count($where)) {
				$query->where($where);
			}
		}

		$events = CalendarEvent::find($query);
		$events->setReturnProperties($returnProperties);

		$this->renderStore(array_merge($events->toArray(), $recurringEvents->toArray()));
	}

	protected function actionRead($calendarId, $eventId, $recurrenceId = null, $returnProperties = "calendarId,groupId,alarms,event[*,attendees,recurrenceRule,attachments]") {

		$calEvent = CalendarEvent::findByPk(['calendarId'=>$calendarId,'eventId'=>$eventId]);

		if (!$calEvent) {
			throw new NotFound();
		}
		if($recurrenceId !== null) {
			$calEvent->addRecurrenceId(new DateTime($recurrenceId));
		}

		$this->renderModel($calEvent, $returnProperties);
	}

	protected function actionNew($calendarId, $returnProperties = "*,alarms,event[*,attendees]") {

		$calendar = Calendar::findByPk($calendarId);
		if(empty($calendar)) {
			throw new NotFound('Calendar not found');
		}
		
		$this->renderModel($calendar->newEvent(), $returnProperties);
	}

	/**
	 * Create a new field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"field":{"attributes":{"fieldname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($calendarId, $returnProperties = "") {

		$calEvent = Calendar::findByPk($calendarId)->newEvent(); 
		$calEvent->setValues(IFW::app()->getRequest()->body['data']);
		$calEvent->save();

		$this->renderModel($calEvent, $returnProperties);
	}

	/**
	 * Update event PUT
	 * 
	 * @param int $calendarId The ID of calendar
	 * @param int $id The ID of the event
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($calendarId, $eventId, $recurrenceId = null, $single = '1', $returnProperties = "calendarId,groupId,alarms,event[*,attendees,recurrenceRule,attachments]") {

		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId,'calendarId'=>$calendarId]);

		if (!$calEvent) {
			throw new NotFound();
		}
	
		if($recurrenceId !== null) {
			$calEvent->addRecurrenceId(new DateTime($recurrenceId));
		}
		
		$calEvent->setValues(IFW::app()->getRequest()->body['data']);
		$success = ($single === '1') ? $calEvent->save() : $calEvent->saveFromHere();

		$this->renderModel($calEvent, $returnProperties);
	}

	/**
	 * Will delete event + attendees for now
	 *
	 * Delete your attendence to the event.
	 * If you are the last attending person delete the event as well.
	 * @todo: If you are the organizer and want to cancel the event. Call actionCancel()
	 *
	 * @param int $calendarId
	 * @param int $id of the event
	 * @param string $recurrenceId Start time of occurrence when recurring
	 * @param bool $single when true delete a single occurrence
	 * @throws NotFound
	 */
	public function actionDelete($calendarId, $eventId, $recurrenceId = null, $single = '1') {
		$calEvent = CalendarEvent::findByPk(['eventId'=>$eventId, 'calendarId'=>$calendarId]);
		if (!$calEvent) {
			throw new NotFound();
		}
		if($recurrenceId !== null) {
			$calEvent->addRecurrenceId(new DateTime($recurrenceId));
		}
		$success = ($single === '1') ? $calEvent->delete() : $calEvent->deleteFromHere();

		$this->renderModel($calEvent);
	}

	public function actionImportICS() {

	}

	protected function actionDownload($id) {
		$event = Event::findByPk($id);
		$vObject = \GO\Modules\GroupOffice\Calendar\Model\ICalendarHelper::toVObject($event);
		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: inline; filename=calendar.ics');
		echo $vObject->serialize();
	}
	

}