<?php
namespace IFW\Util;

use DateTime as PHPDateTime;

class DateTime extends PHPDateTime{
	
	/**
	 * The date outputted to the clients. It's according to ISO 8601;	 
	 */
	const FORMAT_API = "c";
	
//	public function toArray($properties = null) {
//		 return $this->format(self::FORMAT_API);		
//	}
	
	public function toLocaleFormat($withTime = false) {
		$format = 'd-m-Y';
		
		if($withTime) {
			$format .= ' G:i';
		}
		
		return $this->format($format);
	}

}
