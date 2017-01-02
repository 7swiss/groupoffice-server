<?php

namespace IFW\Db;

use IFW\Mail\RecipientList;
use PHPUnit_Framework_TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class RecipientListTest extends PHPUnit_Framework_TestCase {

	public function testTimezone() {
		
		$str = 'los@email.nl,nogeen@naam.nl, "Met naam" <mer@naam.nl>, "Comma , in naam" <comma@naam.nl>, ongeldig, <ongeldig2>';
		$list = new RecipientList($str);
		
		
		
		
	}
}
