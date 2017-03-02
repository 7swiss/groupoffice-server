<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Files\Model;

use DateTime;
//use GO\Core\Orm\Record;
use \IFW\Orm\PropertyRecord;
use GO\Core\Users\Model\User;
use IFW\Auth\Permissions\ViaRelation;

/**
 */
class NodeUser extends PropertyRecord {

	/**
	 * 
	 * @var int
	 */							
	public $nodeId;

	/**
	 * 
	 * @var int
	 */							
	public $userId;

	/**
	 * 
	 * @var bool
	 */							
	public $starred = false;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $touchedAt;

	public static function tableName() {
		return 'files_node_user';
	}

	protected static function defineRelations() {
		self::hasOne('node', Node::class, ['nodeId' => 'id']);
		self::hasOne('user', User::class, ['userId' => 'id']);
	}

}
