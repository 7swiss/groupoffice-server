<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

/**
 * A period of time which is part of a schedual
 *
 * @author mdhart
 */
class Period {
	
	//TYPE
	const Busy = 1;
	const Unavailable = 2;
	const Free = 3;
	const Tentative = 4;
	
	/**
	 * Start of period
	 * @var Datetime
	 */
	public $startAt;
	
	/**
	 * end of period
	 * @var Datetime
	 */
	public $endAt;
	
	public $type;
	
	public function Period(Schedual $schedual) {
		$this->schedual = $schedual;
	}
	
}
