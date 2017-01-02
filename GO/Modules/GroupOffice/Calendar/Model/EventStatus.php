<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

/* ENUM */

class EventStatus {

	const __default = self::NeedsAction;
    
	const NeedsAction = 1;
	const Confirmed = 2;
	const Tentative = 3;
	const Cancelled = 4;

	static public $text = [
		self::Cancelled => 'CANCELLED',
		self::Tentative => 'TENTATIVE',
		self::Confirmed => 'CONFIRMED',
		self::NeedsAction => 'NEEDS-ACTION',
	];

	static public function fromText($text) {
		$role = array_search((string)$text, self::$text);
		if($role === false) {
			return self::__default;
		}
		return $role;
	}

}
