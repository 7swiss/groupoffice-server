<?php

namespace GO\Core\Users\Controller;

use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Db\Criteria;
use IFW\Orm\Query;
use GO\Core\Users\Model\Group;
use GO\Core\Users\Model\UserGroup;

class UserGroupsController extends Controller {

	protected function httpGet($userId, $orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $availableOnly = false) {

		if ($availableOnly) {
			$groups = Group::find((new Query())
									->orderBy([$orderColumn => $orderDirection])
									->limit($limit)
									->offset($offset)
									->search($searchQuery, array('t.name'))
									->join(
											UserGroup::tableName(), 'userGroup', (new Criteria())
											->where('t.id = userGroup.groupId')
											->andWhere(['userGroup.userId' => $userId])
											,  'LEFT')
									->where(['userGroup.groupId' => null])
			);
		} else {
			$groups = Group::find((new Query())
									->orderBy([$orderColumn => $orderDirection])
									->limit($limit)
									->offset($offset)
									->search($searchQuery, array('t.name'))
									->joinRelation('userGroup')
									->groupBy(['t.id'])
									->where(['userGroup.userId' => $userId]));
		}

		$this->renderStore($groups);
	}

}
