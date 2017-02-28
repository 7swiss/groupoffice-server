<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Calendar\Model;

use GO\Core\Auth\Permissions\Model\GroupAccess;

/**
 * A Node can be a File or a Folder
 * The time depends on the object it is attached to
 *
 */
class CalendarGroup extends GroupAccess {

	/**
	 * PK
	 * @var int
	 */							
	public $calendarId;

	/**
	 * PK
	 * @var int
	 */							
	public $groupId;

	/**
	 * 
	 * @var bool
	 */							
	public $canRead = true;

	/**
	 * 
	 * @var bool
	 */							
	public $canWrite = false;

	protected static function groupsFor() {
		return self::hasOne('calendar', Calendar::class, ['calendarId' => 'id']);
	}

}
