<?php

namespace GO\Core\Users\Controller;

use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Db\Criteria;
use IFW\Orm\Query;
use GO\Core\Users\Model\User;
use GO\Core\Users\Model\UserGroup;

class GroupUsersController extends Controller {

	protected function httpGet($groupId, $orderColumn = 'username', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $availableOnly = false) {

		if ($availableOnly) {
			$users = User::find((new Query())
									->orderBy([$orderColumn => $orderDirection])
									->limit($limit)
									->offset($offset)
									->search($searchQuery, ['t.username'])
									->join(
											UserGroup::tableName(), 
											'userGroup', 
													(new Criteria())
														->where('t.id = userGroup.userId')
														->andWhere(['userGroup.groupId' => $groupId])
											, 'LEFT')
									->where(['userGroup.userId' => null])
			);
		
		} else {
			$users = User::find((new Query())
									->orderBy([$orderColumn => $orderDirection])
									->limit($limit)
									->offset($offset)
									->search($searchQuery, ['t.username'])
									->joinRelation('userGroup')
									->groupBy(['t.id'])
									->where(['userGroup.groupId' => $groupId])
			);
		}

		$this->renderStore($users);
	}

}
