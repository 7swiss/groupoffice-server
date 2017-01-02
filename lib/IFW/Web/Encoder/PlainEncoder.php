<?php
namespace IFW\Web\Encoder;


class PlainEncoder implements EncoderInterface {
	
	public function encode($responseData) {
		$str = '';
		foreach($responseData as $key => $value) {
			$str .= $key.': '.  json_encode($value, JSON_PRETTY_PRINT)."\n";
		}
		
		return $str;
	}
	
	public function decode($requestBody) {
		return $requestBody;
	}

	public static function getContentType() {
		return 'text/plain;charset=UTF-8;';
	}
}