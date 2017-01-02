<?php
namespace GO\Modules\GroupOffice\Tasks\Model;
						
use GO\Core\Orm\Record;
						
/**
 * The TaskComment record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class TaskComment extends Record{


	/**
	 * 
	 * @var int
	 */							
	public $taskId;

	/**
	 * 
	 * @var int
	 */							
	public $commentId;

}