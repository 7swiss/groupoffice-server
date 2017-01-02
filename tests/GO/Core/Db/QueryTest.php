<?php
namespace IFW\Db;

use GO\Modules\Contacts\Model\Address;
use PHPUnit_Framework_TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class QueryTest extends PHPUnit_Framework_TestCase {
	public function testMergeWith() {
		
		$query1 = new \IFW\Orm\Query();
		$query1->select('t.*')
						->debug()
						->join(Address::class,'addresses','t.id=addresses.contactId')
						->joinRelation('emailAddresses');
		
		$query2 = (new \IFW\Orm\Query)->joinRelation('phoneNumbers');
		
		$query1->mergeWith($query2);
		
		$this->assertEquals(3, count($query1->joins));

		$this->assertEquals(true, $query1->debug);

	}
}
