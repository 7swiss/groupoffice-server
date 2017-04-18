<?php

namespace IFW\Mail;

use IFW\Mail\RecipientList;
use PHPUnit\Framework\TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class RecipientListTest extends TestCase {

	public function testTimezone() {
		
		$str = 'los@email.nl,nogeen@naam.nl, "Met naam" <mer@naam.nl>, "Comma , in naam" <comma@naam.nl>, ongeldig, <ongeldig2>';
		$list = new RecipientList($str);
		
		$array = $list->toArray();
		
		
		$this->assertEquals(6, count($array));
		
		$this->assertEquals("Comma , in naam", $array[3]->personal);
		
	}
}
