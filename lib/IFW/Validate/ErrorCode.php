<?php
namespace IFW\Validate;

class ErrorCode {
	
	const REQUIRED = 1;
	
	const MALFORMED = 2;
	
	/**
	 * Delete impossible because it\'s in use
	 */
	const INUSE = 3;
	
	const RELATIONAL = 4;
	
	const NOT_FOUND = 5;
	
	const TIMEZONE_INVALID = 6;
	
	const CONFLICT = 7;
	
	const DEPENDENCY_NOT_SATISFIED = 8;
	
	const CONNECTION_ERROR = 9;
	
	const INVALID_INPUT = 10;
	
	const UNIQUE = 11;
	
	
	static $descriptions = [
			self::REQUIRED => 'Property is required',
			self::MALFORMED => 'Value is malformed',
			self::INUSE => 'Delete impossible because it\'s in use',
			self::RELATIONAL => 'Error occured in a related record',
			self::NOT_FOUND => 'Not found',
			self::TIMEZONE_INVALID => 'Time zone is invalid',
			self::CONFLICT => 'Conflict',
			self::DEPENDENCY_NOT_SATISFIED => 'Dependency not satisfied',
			self::CONNECTION_ERROR => 'Error while establishing connection',
			self::INVALID_INPUT => 'Invalid input'
	];	
	
	static function getDescription($code) {		
		return isset(self::$descriptions[$code]) ? self::$descriptions[$code] : '';
	}
}
