<?php

namespace GO\Modules\GroupOffice\Calendar\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Calendar\Model\Attendee;
use GO\Modules\GroupOffice\Calendar\Model\Event;
use GO\Modules\GroupOffice\Calendar\Model\User;
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
	public function actionStore($orderColumn = 't.startAt', $orderDirection = 'ASC', $from = false, $until = false, $searchQuery = "", $returnProperties = "*,attendees", $where = null) {

		$query = (new Query)
			->joinRelation('recurrenceRule', false, 'LEFT')
			->orderBy([$orderColumn => $orderDirection]);
		
		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['title']);
			$recurringEvents = new IFW\Data\Store([]); // does not find recurring events
		} else {
			$query->where('startAt <= :until AND endAt > :from')->bind([':until' => $until, ':from' => $from]);
			$recurringEvents = Event::findRecurring(new \DateTime($from), new \DateTime($until));
			$recurringEvents->setReturnProperties($returnProperties);
		}

		$query->andWhere('recurrenceRule.frequency IS NULL');

		if (!empty($where)) {

			$where = json_decode($where, true);

			if (count($where)) {
				$query->where($where);
			}
		}

		$events = Event::find($query);
		$events->setReturnProperties($returnProperties);

		$this->renderStore(array_merge($events->toArray(), $recurringEvents->toArray()));
	}

	protected function actionRead($eventId,$userId,$occurrenceTime = null, $returnProperties = "calendarId,userId,alarms,event[*,attendees,recurrenceRule,attachments]") {

		$attendee = Attendee::findByPk(['eventId'=>$eventId,'userId'=>$userId]);

		if (!$attendee) {
			throw new NotFound();
		}
		if($occurrenceTime !== null) {
			// fake a start time and ask to change single instance or start new series
			$attendee->event->setStartTime(new DateTime('@'.$occurrenceTime));
		}

		$this->renderModel($attendee, $returnProperties);
	}

	protected function actionDownload($id) {
		$event = Event::findByPk($id);
		$vObject = \GO\Modules\GroupOffice\Calendar\Model\ICalendarHelper::toVObject($event);
		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: inline; filename=calendar.ics');
		echo $vObject->serialize();
	}

	protected function actionNew($returnProperties = "*,alarms,event[*,attendees]"){
		$attendee = Attendee::me();
		
		$this->renderModel($attendee, $returnProperties);
	}

	/**
	 * Create a new field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"field":{"attributes":{"fieldname":"test",...}}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = "") {

		$attendee = Attendee::me();

		$attendee->setValues(IFW::app()->getRequest()->body['data']);
//		if($attendee->event->save()) {
//
//		}
		$attendee->save();

		$this->renderModel($attendee, $returnProperties);
	}

	/**
	 * Update event PUT
	 * 
	 * @param int $eventId The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($eventId, $userId, $returnProperties = "calendarId,userId,alarms,event[*,attendees,recurrenceRule,attachments]") {

		$attendee = Attendee::findByPk(['eventId'=>$eventId,'userId'=>$userId]);

		if (!$attendee) {
			throw new NotFound();
		}

		$attendee->setValues(IFW::app()->getRequest()->body['data']);
		$attendee->save();

		$this->renderModel($attendee, $returnProperties);
	}

	/**
	 * Will delete event + attendees for now
	 *
	 * Delete your attendence to the event.
	 * If you are the last attending person delete the event as well.
	 * @todo: If you are the organizer and want to cancel the event. Call actionCancel()
	 *
	 * @param int $eventId
	 * @throws NotFound
	 */
	public function actionDelete($eventId, $userId) {
		$attendee = Attendee::findByPk(['eventId'=>$eventId, 'userId'=>$userId]);

		if (!$attendee) {
			throw new NotFound();
		}

		$attendee->delete();

		$this->renderModel($attendee);
	}

	public function actionImportICS() {

	}
	

}