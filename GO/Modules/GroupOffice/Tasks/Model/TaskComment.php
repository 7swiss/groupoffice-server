<?php
namespace GO\Modules\GroupOffice\Tasks\Model;

use GO\Core\Comments\Model\Comment;
use GO\Core\Notifications\Model\Notification;
use IFW\Auth\Permissions\ViaRelation;
						
/**
 * The TaskComment record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class TaskComment extends Comment {


	/**
	 * 
	 * @var int
	 */							
	public $taskId;

	
	protected static function defineRelations() {
		parent::defineRelations();
		
		self::hasOne('task', Task::class, ['taskId' => 'id']);
	}
	
	
	protected static function internalGetPermissions() {
		return new ViaRelation('task');
	}
	
	protected function internalSave() {
		
		if($this->isNew()) {
			
			$data = $this->task->toArray('id,description,dueAt');
			$data['excerpt'] = \IFW\Util\StringUtil::cutString(strip_tags($this->content), 50);
			
			if(!Notification::create('comment', $data, $this->task)){			
				return false;
			}
		}
		
		return parent::internalSave();
	}

}