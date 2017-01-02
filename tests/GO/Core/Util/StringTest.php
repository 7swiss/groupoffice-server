<?php
namespace IFW\Util;

use IFW\Imap\Utils;
use PHPUnit_Framework_TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class StringTest extends PHPUnit_Framework_TestCase {
	public function testMimeHeader() {

		$mime = "=?utf-8?q?=F0=9F=92=AC_Your_opinion_of_Tern_Hill_Hall?=";
		
//		$decoded = Utils::mimeHeaderDecode($mime);
		
//		$this->assertEquals("💬 Your opinion of Tern Hill Hall", $decoded);
	}
}