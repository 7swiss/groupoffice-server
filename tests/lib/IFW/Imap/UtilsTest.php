<?php
namespace IFW\Imap;

use PHPUnit\Framework\TestCase;


class UtilsTest extends TestCase {
	public function testMimeHeader() {
		
		$this->markTestSkipped('must be revisited.');

		$mime = "=?utf-8?q?=F0=9F=92=AC_Your_opinion_of_Tern_Hill_Hall?=";
		
		$decoded = Utils::mimeHeaderDecode($mime);
		
		$this->assertEquals("💬 Your opinion of Tern Hill Hall", $decoded);
	}
}
