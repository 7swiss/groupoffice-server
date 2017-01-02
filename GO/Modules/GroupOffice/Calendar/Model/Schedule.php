<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

/**
 * The schedual belongs to a principal, it show when they are free of busy
 *
 * @author mdhart
 */
class Schedule {

	protected $userId;
	protected $resourceId;
	
	public static function byUser($id) {
		$self = new self();
		$self->userId = $id;
		return $self;
	}
	
	public static function byResource($id) {
		$self = new self();
		$self->resourceId = $id;
		return $self;
	}
	
	/**
	 * Fetch an array of periodes where the principal is either free, busy, tentative or Unavailable
	 * @param Datetime $from
	 * @param Datetime $till
	 */
	public function freeBusy($from, $till) {
		//get array of period items
	}
}
