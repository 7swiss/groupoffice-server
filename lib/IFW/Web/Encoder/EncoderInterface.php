<?php
namespace IFW\Web\Encoder;

/**
 * Content encoder
 */
interface EncoderInterface {
	
	/**
	 * Get content type.
	 * eg. "application/json;charset=UTF-8;"
	 * @param string
	 */
	public static function getContentType();
	
	/**
	 * Encode the reponse
	 * 
	 * @param $responseData
	 * @param string
	 */
	public function encode($responseData);
	
	/**
	 * Decode the request input
	 * 
	 * @param string $requestBody
	 */
	public function decode($requestBody);	
}