<?php
namespace IFW\Db;

use GO\Modules\GroupOffice\Contacts\Model\EmailAddress;
use PHPUnit_Framework_TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class QueryBuilderTest extends PHPUnit_Framework_TestCase {
	public function testToString() {
		
		$contactId = 1;
		
		$queryBuilder = (new \IFW\Orm\Query())
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
						->join(EmailAddress::class,'ca','ca.email=addresses.address')
						->where(['ca.contactId' => $contactId])
						->groupBy(['t.id'])
						->getBuilder(\GO\Modules\GroupOffice\Messages\Model\Thread::class);
		
		$sql = $queryBuilder->build(true);
		
		$this->assertStringStartsWith("SELECT", $sql);
		

	}
}