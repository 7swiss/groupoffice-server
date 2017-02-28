<?php

namespace GO\Modules\GroupOffice\Calendar\Controller;

use IFW;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Calendar\Model\Group;
//use GO\Core\Users\Model\Group;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for users
 *
 * See {@see Event} model for the available properties

 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class UserController extends Controller {

	/**
	 * Fetch users
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function actionStore($orderColumn = 't.id', $orderDirection = 'ASC', $limit = 20, $offset = 0, $searchQuery = "", $returnProperties = "", $where = null) {

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

		$groups = Group::find($query);
		$groups->setReturnProperties($returnProperties);

		$this->renderStore($groups);
	}

	
}
