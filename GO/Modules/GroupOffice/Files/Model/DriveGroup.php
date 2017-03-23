<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Files\Model;

use GO\Core\Orm\Record;
use GO\Core\Users\Model\Group;
use GO\Core\Auth\Permissions\Model\GroupAccess;

/**
 * A Node can be a File or a Folder
 * The time depends on the object it is attached to
 *
 */
class DriveGroup extends GroupAccess {

	/**
	 * PK
	 * @var int
	 */							
	public $driveId;

	/**
	 * PK
	 * @var int
	 */							
	public $groupId;

	/**
	 * 
	 * @var bool
	 */							
	public $write = false;

	public $manage = false;

	protected static function groupsFor() {
		return self::hasOne('drive', Drive::class, ['driveId' => 'id']);
	}
	

}
