<?php
namespace IFW\Data;

use ArrayAccess;
use ArrayIterator;
use Exception;
use IteratorAggregate;


/**
 * Parses return attributes from string to array
 * 
 * It's mainly used by {@see \IFW\Data\Model::toArray()}
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ReturnProperties implements ArrayAccess, IteratorAggregate {

	/**
	 *
	 * @var array 
	 */
	private $properties = [];
	
	/**
	 * 
	 * Parse returnProperties parameter passed to API
	 * 
	 * In the API the return attributes may be passed as a string.
	 * This will be parsed into an array. eg turn this:
	 * 
	 * 'name,owner[username,id],hasmanyRelation[someProp]' 
	 * 
	 * into:
	 * 
	 * ['name'=>'','owner' => 'username,id','hasmanyRelation' => 'someProp']
	 * 
	 * An array of properties can also be supplied
	 * 
	 * @see Model::toArray() for more information
	 * 
	 * @param string|string[] $properties	 
	 * 
	 */
	public function __construct($properties, $defaultProperties) {
		
		if(empty($properties)) {
			$properties = $defaultProperties;
		}
		
		if(is_string($properties)) {
			$this->properties = $this->parseString($properties);
		}else if(is_array($properties)) {
			foreach($properties as $property) {
				$this->properties[$property]='';
			}
		}  else {
			throw new \Exception("Invalid properties specicication given");				
		}

		if(isset($this->properties['*'])) {
			unset($this->properties['*']);			
			$this->properties = array_merge($this->properties, $this->parseString($defaultProperties));
		}
		
	}

	private function parseString($str) {

		$attr = [];

		//remove whitespace		
		$str = str_replace(' ', '', $str);

		if (empty($str)) {
			return [];
		}

		$inBracketCount = 0;
		$currentAttributeName = '';
		$subReturnAttributes = "";

		for ($i = 0, $l = strlen($str); $i < $l; $i++) {
			$char = $str[$i];
			switch ($char) {
				case ',':
					if (!$inBracketCount) {
						$attr[$currentAttributeName] = $subReturnAttributes;
						$currentAttributeName = '';
						$subReturnAttributes = '';
					} else {
						$subReturnAttributes .= $char;
					}
					break;

				case '[':

					if ($inBracketCount) {
						$subReturnAttributes .= $char;
					}

					$inBracketCount++;
					break;

				case ']':
					$inBracketCount--;
					if ($inBracketCount) {
						$subReturnAttributes .= $char;
					}
					break;

				default:
					if ($inBracketCount) {
						$subReturnAttributes .= $char;
					} else {
						$currentAttributeName .= $char;
					}
					break;
			}
		}

		$attr[$currentAttributeName] = $subReturnAttributes;

		return $attr;
	}

	public function offsetExists($offset) {
		return isset($this->properties[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->properties[$offset]) ? $this->properties[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		$this->properties[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->properties[$offset]);
	}

	public function getIterator() {
		return new ArrayIterator($this->properties);
	}
}