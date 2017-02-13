<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Files\Model;

use GO\Core\Orm\Record;
use GO\Core\Users\Model\Group;

/**
 * A Node can be a File or a Folder
 * The time depends on the object it is attached to
 *
 */
class NodeGroup extends Record {

	/**
	 * PK
	 * @var int
	 */							
	public $nodeId;

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

	protected static function defineRelations() {
		self::hasOne('node', Node::class, ['nodeId'=>'id']);
		self::hasOne('group', Group::class, ['groupId'=>'id']);
	}

}
