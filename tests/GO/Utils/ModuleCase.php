<?php

namespace GO\Utils;

abstract class ModuleCase extends \PHPUnit\Framework\TestCase {

	use UserTrait;

	static function setUpBeforeClass() {
		$mod = new static::$module();
		if(!$mod->isInstalled()) {
			$moduleRecord = new \GO\Core\Modules\Model\Module();
			$moduleRecord->name = static::$module;
			$moduleRecord->save();
		}

		\IFW::app()->reinit();	
	}

}