<?php
namespace IFW\Util;

use PHPUnit\Framework\TestCase;

class CryptTest extends TestCase {
	
	public function testEncrypt(){
		
		$secret = "We attack at dawn";

		$crypt = new Crypt();		
		$encrypted = $crypt->encrypt($secret);
		
		$this->assertEquals(true, $crypt->isEncrypted($encrypted));
		
		$decrypted = $crypt->decrypt($encrypted);
		
		$this->assertEquals($secret, $decrypted);
	} 
	
}
