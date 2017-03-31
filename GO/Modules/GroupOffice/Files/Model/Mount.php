<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Files\Model;

//use GO\Core\Orm\Record;
use \IFW\Orm\PropertyRecord;
use \IFW\Orm\Record;
use GO\Core\Users\Model\User;

/**
 */
class Mount extends Record {

	/**
	 *
	 * @var int
	 */
	public $driveId;

	/**
	 *
	 * @var int
	 */
	public $userId;

	protected static function defineRelations() {
		self::hasOne('drive', Drive::class, ['driveId' => 'id']);
		self::hasOne('user', User::class, ['userId' => 'id']);
	}

}
