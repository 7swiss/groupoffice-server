<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;


use IFW\Auth\Permissions\Model;
use GO\Core\Users\Model\UserGroup;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;

class EventPermissions extends Model {

	private $userGroup;

	protected function internalCan($permissionType, UserInterface $user) {

		return true;
		switch($permissionType) {
			case self::PERMISSION_CREATE:
				return true;
			default:
				return $this->getUserGroup($user) != false;
		}

		return false;
	}

	private function getUserGroup($user) {
		if(!isset($this->userGroup)) {

			return $this->userGroup = UserGroup::find(['groupId' => $this->record->groupId, 'userId' => $user->id()])->single();
		}

		return $this->userGroup;
	}

	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		//$query->join(Attendee::tableName(), 'a', 't.id = a.eventId', 'LEFT');
		$subQuery = Attendee::find(
				(new Query)
				->tableAlias('attendees')
				->joinRelation('calendarGroups.groupUsers')
				->where(["groupUsers.userId" => $user->id()])
				->andWhere('attendees.eventId = '.$query->getTableAlias().'.id')
				);



//				UserGroup::find(
//						(new Query())
//						->tableAlias('ug')
//						->where(['userId' => $user->id()])
//						->andWhere('ug.groupId = a.groupId')
//						);

		$query->allowPermissionTypes([\IFW\Auth\Permissions\Model::PERMISSION_READ])
						->andWhere(['EXISTS', $subQuery]);
	}
}