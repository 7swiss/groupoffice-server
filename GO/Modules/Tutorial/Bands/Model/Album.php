<?php

namespace GO\Modules\Tutorial\Bands\Model;

use GO\Core\Users\Model\User;
use IFW\Orm\Record;

/**
 * The Album model
 *
 * @property int $id
 * @property int $bandId
 * @property StringUtil $name
 * @property int $ownerUserId
 * @property User $owner
 * @property StringUtil $createdAt
 * @property StringUtil $modifiedAt
 * 
 * @property Band $band
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Album extends Record {

	protected static function defineRelations() {
		self::hasOne('band', Band::class, ['bandId' => 'id']);
		self::hasOne('owner', User::class, ['createdBy' => 'id']);

		//Define this new relation in the user model too.
		User::hasMany('albums', Album::class, ['id' => 'createdBy']);

		parent::defineRelations();
	}

}
