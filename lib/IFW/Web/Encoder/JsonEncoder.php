<?php
namespace IFW\Web\Encoder;

use Exception;

class JsonEncoder implements EncoderInterface {
	
	public function encode($responseData) {

		$responseBody = json_encode($responseData, JSON_PRETTY_PRINT);
		
		if(empty($responseBody)){
			throw new Exception("JSON encoding error: '".json_last_error_msg()."'.\n\nArray data from server:\n\n".var_export($responseData, true));
		}
		
		return $responseBody;
	}
	
	public function decode($requestBody) {
		
		$data = $requestBody != "" ? json_decode($requestBody, true) : [];
					
		// Check if the post is filled with an array. Otherwise make it an empty array.
		if(!is_array($data)){				
			throw new Exception("JSON decoding error: '".json_last_error_msg()."'.\n\nJSON data from client: \n\n".var_export($requestBody, true));
		}
		
		return $data;
	}

	public static function getContentType() {
		return 'application/json;charset=UTF-8;';
	}
}