<?php

namespace GO\Modules\GroupOffice\Tasks\Model;

use GO\Modules\GroupOffice\Tasks\Model\Task;
use GO\Utils\ModuleCase;
use GO\Utils\UserTrait;


/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class TaskTest extends ModuleCase {
	
	use UserTrait;


	public function testJoinRelation() {
		
		$this->changeUser('henk');
	
		$task = new Task();
		$task->description = 'Task for henk';
		$success = $task->save();
		$this->assertEquals(true, $success);
		
		$this->assertEquals(true, $task->assignee->equals(GO()->getAuth()->user()->group));
		
		$this->changeUser('admin');
	}
	

}
