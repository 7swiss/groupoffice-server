<?php

namespace GO\Modules\Calendar;

abstract class AbstractCalendarCase extends \PHPUnit\Framework\TestCase {

	function setUp() {
		$mod = new \GO\Modules\GroupOffice\Calendar\Module();
		if(!$mod->isInstalled()) {
			$moduleRecord = new \GO\Core\Modules\Model\Module();
			$moduleRecord->name = \GO\Modules\GroupOffice\Calendar\Module::class;
			$moduleRecord->save();
		}

		\IFW::app()->reinit();
	}
}