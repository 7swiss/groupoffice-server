<?php
namespace GO\Modules\GroupOffice\Tasks\Model;

use GO\Core\Tags\Model\Tag;
use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Record;

/**
 * The task tag model
 *
 * @property Task $task
 * @property Tag $tag
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Wesley Smits <wsmits@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class TaskTag extends Record{		
	
	/**
	 * 
	 * @var int
	 */							
	public $taskId;

	/**
	 * 
	 * @var int
	 */							
	public $tagId;

	public static function defineRelations(){
		self::hasOne('task', Task::class, ['taskId' => 'id']);
		self::hasOne('tag', Tag::class, ['tagId' => 'id']);
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation( 'task');
	}
}