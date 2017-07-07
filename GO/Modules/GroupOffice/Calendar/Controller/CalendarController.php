<?php

namespace GO\Modules\GroupOffice\Calendar\Controller;

use IFW;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Calendar\Model\Calendar;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for calendars
 *
 * See {@see Event} model for the available properties

 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class CalendarController extends Controller {

	/**
	 * Fetch calendars
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function store($orderColumn = 't.id', $orderDirection = 'ASC', $limit = 20, $offset = 0, $searchQuery = "", $returnProperties = "*,defaultAlarms", $where = null) {

		$query = (new Query)
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset);

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['name']);
		}

		if (!empty($where)) {

			$where = json_decode($where, true);

			if (count($where)) {
				$query->whereSafe($where);
			}
		}

		//$this->getFilterCollection()->apply($query);

		$calendars = Calendar::find($query);
		$calendars->setReturnProperties($returnProperties);

		$this->renderStore($calendars);
	}

	public function read($id, $returnProperties = "*,defaultAlarms") {

		$calendar = Calendar::findByPk($id);

		if (!$calendar) {
			throw new NotFound();
		}

		$this->renderModel($calendar, $returnProperties);
	}

	public function newInstance($returnProperties = "") {
		$event = new Calendar();
		$this->renderModel($event, $returnProperties);
	}

	/**
	 * Create a new calendar.
	 *
	 * @param array|JSON $returnProperties The attributes to return to the client.
	 */
	public function create($returnProperties = "*,defaultAlarms") {

		$calendar = new Calendar();
		$calendar->setValues(IFW::app()->getRequest()->body['data']);
		$calendar->save();


		$this->renderModel($calendar, $returnProperties);
	}

	/**
	 * Update calendar
	 *
	 * @param int $id The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($id, $returnProperties = "*,defaultAlarms") {

		$calendar = Calendar::findByPk($id);

		if (!$calendar) {
			throw new NotFound();
		}

		$calendar->setValues(IFW::app()->getRequest()->body['data']);
		$calendar->save();

		$this->renderModel($calendar, $returnProperties);
	}

	/**
	 * Delete an event
	 *
	 * @param int $id
	 * @throws NotFound
	 */
	public function delete($id) {
		$calendar = Calendar::findByPk($id);

		if (!$calendar) {
			throw new NotFound();
		}

		$calendar->delete();

		$this->renderModel($calendar);
	}

}
