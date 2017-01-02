<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

/* ENUM RSVP */

class ResponseStatus {

	const __default = self::NeedsAction;
    
    const NeedsAction = 1; // not responded
	const Tentative = 2; // Maybe
	const Accepted = 3; // Yes
	const Declined = 4; // No
	const Delegated = 5; // Someone else is going in my place (not supported yet)

	static public $text = [
		self::NeedsAction => 'NEEDS-ACTION',
		self::Tentative => 'TENTATIVE',
		self::Accepted => 'ACCEPTED',
		self::Declined => 'DECLINED',
		self::Delegated => 'DELEGATED',
	];

	static public function fromText($text) {
		$role = array_search((string)$text, self::$text);
		if($role === false) {
			return self::__default;
		}
		return $role;
	}
}
