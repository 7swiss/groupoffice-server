<?php

namespace GO\Core\Modules\Model;

use Exception;
use GO\Core\Install\Model\System;
use GO\Core\Users\Model\Group;
use IFW;
use IFW\Auth\Permissions\Model;
use IFW\Fs\File;
use IFW\Fs\Folder;
use IFW\Modules\Module as IFWModule;

/**
 * @todo rename to Module. A problem with this exists because the Record is already called Module.
 */
abstract class InstallableModule extends IFWModule {

	/**
	 * Controls if the module is installed by default
	 * 
	 * @return boolean
	 */
	public function autoInstall() {
		return true;
	}

	/**
	 * Executed on module installation
	 * 
	 * @param Module
	 * @return boolean
	 */
	public function install(Module $record) {

		$this->installPermissions($record);
		
		return true;
	}
	
	/**
	 * Executed on module uninstallation
	 * 
	 * @param Module
	 * @return boolean
	 */
	public function uninstall(Module $record) {

		return true;
	}

	/**
	 * By default all permission types are granted to everyone on installation
	 * Override this function if you want to do something else.
	 * 
	 * @param Module $record
	 * @return boolean
	 */
	protected function installPermissions(Module $record) {
		//Grant everyone permission on all actions by default
		$permissionTypes = $this->getPermissions()->getPermissionTypes();
		foreach ($permissionTypes as $type) {
			if (!$type['readonly']) {
				$moduleGroup = new ModuleGroup();
				$moduleGroup->moduleId = $record->id;
				$moduleGroup->action = $type['name'];
				$moduleGroup->groupId = Group::ID_EVERYONE;
				if (!$moduleGroup->save()) {
					throw \Exception("Could not save module group record");
				}
			}
		}
		
		return true;
	}

	private $permissions;

	/**
	 * 
	 * @param Module $record
	 * @return Model
	 */
	public final function getPermissions() {



		if (!isset($this->permissions)) {
			$this->permissions = $this->internalGetPermissions();
		}
		$record = $this->getRecord();
		if ($record) {
			$this->permissions->setRecord($record);
		}

		return $this->permissions;
	}

	/**
	 * Override this to implement own permission types
	 * 
	 * @param Module $record
	 * @return ModuleManagerPermissions
	 */
	protected static function internalGetPermissions() {
		return new ModuleManagerPermissions();
	}

	/**
	 * Get update files.
	 * Can be SQL or PHP scripts.
	 * 
	 * @return File[] The array has the filename date stamp as key so it can be sorted
	 */
	public function databaseUpdates() {

		$folder = new Folder($this->getPath());
		$dbFolder = $folder->getFolder('Install/Database');
		if (!$dbFolder->exists()) {
			return [];
		} else {

			$files = $dbFolder->getChildren(true, false);

			usort($files, function($file1, $file2) {
				return $file1->getName() > $file2->getName();
			});


			$module = $this->getRecord();

			if ($module) {
				$files = array_slice($files, $module->version);
			}


			$regex = '/[0-9]{8}-[0-9]{4}\.(sql|php)/';
			foreach ($files as $file) {
				if (!preg_match($regex, $file->getName())) {
					throw new Exception("The upgrade file '" . $file->getName() . "' is not in the right filename format. It should be YYYYMMDD-HHMM.sql or YYYYMMDD-HHMM.php");
				}
			}

			return $files;
		}
	}

	/**
	 * Return module dependencies
	 * 
	 * Return an array of full classnames. eg.
	 * 
	 * ['GO\IFWModules\Contacts\ContactsIFWModule']
	 * 
	 * 
	 * @param string[]
	 */
	public function depends() {
		return [];
	}

	/**
	 * Return conflicting modules that can't be installed along with this module.
	 * 
	 * Return an array of full classnames. eg.
	 * 
	 * ['GO\IFWModules\Contacts\ContactsIFWModule']
	 * 
	 * 
	 * @param string[]
	 */
	public function conflicts() {
		return [];
	}

	private $record;

	/**
	 * Get the module model
	 * 
	 * @return Module	
	 */
	public function getRecord() {
		if (!isset($this->record)) {
			$this->record = Module::find(['name' => static::class])->single();
		}

		return $this->record;
	}

	public function setRecord(Module $record) {
		$this->record = $record;
	}

	/**
	 * Check if the module is installed
	 * 
	 * @return boolean
	 */
	public function isInstalled() {
		if (!System::isDatabaseInstalled()) {
			return false;
		}
		if (!$this->getRecord()) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if dependecies are availeble for this module
	 * 
	 * @return boolean
	 */
	public function checkDependencies() {
		foreach ($this->depends() as $moduleName) {
			if (!class_exists($moduleName)) {

				IFW::app()->debug("Required dependency " . $moduleName . " is not available for " . $this->getClassName());

				return false;
			}

			$mod = new $moduleName;
			if (!$mod->isInstalled()) {
				IFW::app()->debug("Required dependency " . $moduleName . " is not installed for " . $this->getClassName());

				return false;
			}
		}

		return true;
	}

	/**
	 * Get's all modules that depend on eachother including this module class
	 * 
	 * @param string[]
	 */
	public function getRecursiveDependencies(array &$allDepends = []) {
		$depends = $this->depends();


		foreach ($depends as $depend) {
			if (in_array($depend, $allDepends)) {
				//prevent endless loop
				continue;
			}

			if (!class_exists($depend)) {
				throw new \Exception("Could not find dependency '" . $depend . "' for '" . $this->getClassName() . "'");
			}

			$manager = new $depend;

			$allDepends[] = $depend;
			$manager->getRecursiveDependencies($allDepends);
		}

		if (!in_array(static::class, $allDepends)) {
			$allDepends[] = static::class;
		}

		return $allDepends;
	}

	public function toArray($returnProperties = "*,model") {
		return parent::toArray($returnProperties);
	}

//	/**
//	 * Get the configuration object for this module.
//	 * 
//	 * @return IFWModuleConfig
//	 */
//	public static function config(){
//		return new IFWModuleConfig(static::class);
//	}
}
