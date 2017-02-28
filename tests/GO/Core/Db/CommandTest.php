<?php
namespace IFW\Db;

use GO\Modules\GroupOffice\Contacts\Model\EmailAddress;
use PHPUnit_Framework_TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class CommandTest extends PHPUnit_Framework_TestCase {
	public function testToString() {
		
		$query = (new Query())
						->select('id,username')
						->from('auth_user')
						->where(['id' => 1]);
		
		$stmt = $query->createCommand()->execute();
		
		$record = $stmt->fetch();
		
		$this->assertArrayHasKey('username', $record);
	}
}
