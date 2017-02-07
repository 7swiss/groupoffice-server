<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

/* Type of calendar */

class ResourceType {

	const __default = self::Calendar;
    
    const Calendar = 1;
	const Equipment = 2;
	const Room = 3;

	static public $text = [
		self::Calendar => 'NEEDS-ACTION',
		self::Equipment => 'TENTATIVE',
		self::Room => 'ACCEPTED',
		self::Declined => 'DECLINED',
		self::Delegated => 'DELEGATED',
	];

	static public function fromText($text) {
		$type = array_search((string)$text, self::$text);
		if($type === false) {
			return self::__default;
		}
		return $type;
	}
}
