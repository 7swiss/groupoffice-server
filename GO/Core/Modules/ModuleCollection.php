<?php
namespace GO\Core\Modules;

use GO\Core\Modules\Model\InstallableModule;
use IFW;
use IFW\Modules\ModuleCollection as IfwModuleCollection;

/**
 * Collection of {@see Module}
 * 
 * An instance of this collection is available by calling:
 * 
 * ````````````````````````````````````````````````````````````````````````````
 * $modules = GO()->getModules();
 * 
 * foreach($modules as $moduleClassName) {
 *	echo $moduleClassName;
 * }
 * ````````````````````````````````````````````````````````````````````````````
 * This overrides the framework module collection to support installable modules.
 * 
 */
class ModuleCollection extends IfwModuleCollection {	
	
	public function __construct() {
		parent::__construct();
		
		$modules = $this->modules;
		$this->modules = [];
		foreach($modules as $module) {
			if(IFW::app()->getAuth()->sudo(function() use ($module){
				$instance = new $module;		

				if($instance instanceof InstallableModule) {
					return $instance->isInstalled() && $instance->checkDependencies();
				}else
				{
					return true;
				}
			})) {
				$this->modules[] = $module;
			}
		}
	}
	
}
