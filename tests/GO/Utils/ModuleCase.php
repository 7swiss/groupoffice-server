<?php

namespace GO\Utils;

abstract class ModuleCase extends \PHPUnit\Framework\TestCase {

	use UserTrait;
	
	protected static $module;

	static function setUpBeforeClass() {
		$mod = new static::$module();
		if(!$mod->isInstalled()) {
			$moduleRecord = new \GO\Core\Modules\Model\Module();
			$moduleRecord->name = static::$module;
			if(!$moduleRecord->save()) {
				throw new \Exception("Failed to install module ".static::$module);
			}
		}

		\IFW::app()->reinit();	
	}

}