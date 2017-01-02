<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

/* ENUM */

class Role {

	const __default = self::Required;
    
    const None = 0;
    const Required = 1;
    const Optional = 2;
    const Chair = 3; //Voorzitter

	static public $text = [
		self::None => 'NON-PARTICIPANT',
		self::Required => 'REQ-PARTICIPANT',
		self::Optional => 'OPT-PARTICIPANT',
		self::Chair => 'CHAIR',
	];

	static public function fromText($text) {
		$role = array_search((string)$text, self::$text);
		if($role === false) {
			return self::__default;
		}
		return $role;
	}
}
