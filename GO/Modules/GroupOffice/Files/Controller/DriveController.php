<?php

namespace GO\Modules\GroupOffice\Files\Controller;

use IFW;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Files\Model\Drive;
use IFW\Orm\Query;

/**
 * The controller for calendars
 *
 * See {@see Event} model for the available properties

 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class DriveController extends Controller {

	public function actionStore($orderColumn = 't.name', $orderDirection = 'ASC', $limit = 20, $offset = 0, $searchQuery = "", $returnProperties = "*,owner[name]", $q = null) {
		$query = (new Query)
				  ->joinRelation('owner', 'name')
				  ->limit($limit)
				  ->offset($offset);

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['name']);
		}
		if(isset($q)) {
			$query->setFromClient($q);
		}
	
		$nodes = Drive::find($query);
		$nodes->setReturnProperties($returnProperties);

		$this->responseData['path'] = [];
		
		$this->renderStore($nodes);

	}
}