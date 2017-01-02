<?php
namespace IFW\Util;

class Number {
		
	public static function toLocaleFormat($number, $decimals=null) {
		
		if(!isset($decimals)) {
			$pos = strpos($number, '.');
			if($pos === false) {
				$decimals = 0;
			}else
			{
				//10.00 pos = 2 len=5 
				$decimals = strlen($number) - $pos - 1;
			}
		}
		
		return number_format($number, $decimals, ',', '.');
	}
	
}
