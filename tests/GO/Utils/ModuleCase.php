<?php

namespace GO\Utils;

abstract class ModuleCase extends \PHPUnit\Framework\TestCase {

	use UserTrait;	
	
	static function module() {	
		
		$cls = get_called_class();
		
		$parts = explode('\\', $cls);

		//Only GO \\ Modules \\ Vendor \\ ModuleName
		$moduleParts = array_slice($parts, 0, 4);
		
		return implode('\\', $moduleParts).'\\Module';		
	}

	static function setUpBeforeClass() {
		$cls = static::module();
		

		$mod = new $cls;
		
		if(!$mod->isInstalled()) {
			$moduleRecord = new \GO\Core\Modules\Model\Module();
			$moduleRecord->name = $cls;
			if(!$moduleRecord->save()) {
				throw new \Exception("Failed to install module ".$cls);
			}
		}

		\IFW::app()->reinit();	
	}

}