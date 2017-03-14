<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Auth\Permissions\ViaRelation;
use IFW\Auth\UserInterface;

class EventPermission extends ViaRelation {

	public function __construct() {
		$this->relationName = 'calendar';
	}

	protected function internalCan($permissionType, UserInterface $user) {

		if(empty($this->record->calendarId)) {
			return true;
		}

		$permissionType = ($permissionType == self::PERMISSION_READ) ? self::PERMISSION_READ : self::PERMISSION_UPDATE;

		$relatedRecord = $this->record->calendar;

		if(!isset($relatedRecord)) {
			throw new Exception("Relation calendar is not set in ".$this->record->getClassName().", Maybe you didn't select or set the key?");
		}

		return $relatedRecord->permissions->can($permissionType, $user);
	}
}