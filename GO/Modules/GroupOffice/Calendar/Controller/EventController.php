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

	protected function actionRead($eventId,$userId, $recurrenceId = null, $returnProperties = "calendarId,userId,alarms,event[*,attendees,recurrenceRule,attachments]") {

		$attendee = Attendee::findByPk(['eventId'=>$eventId,'userId'=>$userId]);

		if (!$attendee) {
			throw new NotFound();
		}
		if($recurrenceId !== null) {
			// fake a start time and ask to change single instance or start new series
			$attendee->event->addRecurrenceId(new DateTime($recurrenceId));
		}

		$this->renderModel($attendee, $returnProperties);
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
	public function actionUpdate($eventId, $userId, $recurrenceId = null, $single = '1', $returnProperties = "calendarId,userId,alarms,event[*,attendees,recurrenceRule,attachments]") {

		$attendee = Attendee::findByPk(['eventId'=>$eventId,'userId'=>$userId]);

		if (!$attendee) {
			throw new NotFound();
		}
	
		$attendee->setValues(IFW::app()->getRequest()->body['data']);
		if($recurrenceId !== null) {
			$attendee->event->addRecurrenceId(new DateTime($recurrenceId));
			// modified is cleared after appling exception so set it again
			if(isset(IFW::app()->getRequest()->body['data']['event'])) {
				$attendee->event->setValues(IFW::app()->getRequest()->body['data']['event']);
			}
		}
		$attendee->event->singleInstance = ($single === '1') ? true : false;
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
	 * @param int $userId
	 * @param string $recurrenceId Start time of occurrence when recurring
	 * @param bool $single when true delete a single occurrence
	 * @throws NotFound
	 */
	public function actionDelete($eventId, $userId, $recurrenceId = null, $single = '1') {
		$attendee = Attendee::findByPk(['eventId'=>$eventId, 'userId'=>$userId]);

		if (!$attendee) {
			throw new NotFound();
		}
		$attendee->event->singleInstance = ($single === '1') ? true : false;
		if($recurrenceId !== null) {
			$attendee->event->addRecurrenceId(new DateTime($recurrenceId));
		} 

		$attendee->delete();

		$this->renderModel($attendee);
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