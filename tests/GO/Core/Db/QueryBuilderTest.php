<?php
namespace IFW\Db;

use GO\Modules\GroupOffice\Contacts\Model\EmailAddress;
use PHPUnit\Framework\TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class QueryBuilderTest extends \PHPUnit\Framework\TestCase {
	public function testToString() {
		
		$contactId = 1;
		
		$command = (new \IFW\Orm\Query())
						->setRecordClassName(\GO\Modules\GroupOffice\Messages\Model\Thread::class)						
						->select('ca.contactId, 
	t.id AS modelId, 
	"GO\\\\Modules\\\\Messages\\\\Model\\\\Thread" AS `modelName`, 
	t.date AS createdAt, 
	t.subject AS description, 
	"" AS tags, 
	_excerpt AS notes,
	CONCAT(\'{"accountId" : \', t.accountId, \'}\') AS `data`')
						->joinRelation('messages.addresses', false)
//						->joinModel(ContactEmailAddress::class, 'email', 'ca', 'email', 'addresses')
						->join(EmailAddress::tableName(),'ca','ca.email=addresses.address')
						->where(['ca.contactId' => $contactId])
						->groupBy(['t.id'])->createCommand();
						
		
		$sql = (string) $command;
		
		$this->assertStringStartsWith("SELECT", $sql);
		

	}
}