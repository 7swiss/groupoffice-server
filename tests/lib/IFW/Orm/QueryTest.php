<?php
namespace IFW\Orm;

use PHPUnit\Framework\TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class QueryTest extends TestCase {
	public function testMergeWith() {
		
		$query1 = new Query();
		$query1->select('t.*')
						->join("tableA",'addresses','t.id = addresses.contactId')
						->joinRelation('example1');
		
		$query2 = (new Query)->joinRelation('example2');
		
		$query1->mergeWith($query2);
		
		$this->assertEquals(3, count($query1->joins));

	}
}
