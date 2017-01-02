<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

/* ENUM */

class Visibility {

	const __default = self::cPublic;

	/**
	 * The visibility of the event is determined by the ACLs of the calendar.
	 */
	const Confidential = 1;
	/**
	 * The details of this event are visible to everyone with at least FreeBusy access to the calendar.
	 */
	const cPublic = 2;
	/**
	 * The details of this event are only visible to users with at least writer access to the calendar.
	 */
	const cPrivate = 3;

	static public $text = [
		self::cPublic => 'PUBLIC',
		self::Confidential => 'CONFIDENTIAL',
		self::cPrivate => 'PRIVATE',
	];

	static public function fromText($text) {
		$role = array_search((string)$text, self::$text);
		if($role === false) {
			return self::__default;
		}
		return $role;
	}

}
