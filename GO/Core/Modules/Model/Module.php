<?php
namespace GO\Core\Modules\Model;

use Exception;
use GO\Core\Users\Model\Group;
use GO\Core\Files\Model\File;
use GO\Core\Module as CoreModule;
use IFW;
use IFW\Db\Utils;
use IFW\Orm\Record;
use ReflectionClass;

/**
 * Module model
 * 
 * Each module that can be used in the application must have a database entry.
 *
 * @property bool $installed 
 * 
 * @property ModuleGroup $groups
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Module extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var string
	 */							
	public $type = 'user';

	/**
	 * 
	 * @var int
	 */							
	public $version = 0;

	/**
	 * When a module is installed it will install dependencies allong
	 * This boolean prevents an endless loop installation
	 * 
	 * @var boolean  
	 */
	protected $dontInstallDatabase = false;
	
	protected static function defineRelations() {
		
		self::hasMany('groups', ModuleGroup::class, ['id'=> 'moduleId']);
		
		parent::defineRelations();
	}
	
	
	
	public function getInstalled(){
		return !$this->isNew() && !$this->deleted;
	}
	
	public function isAvailable() {
		return class_exists($this->name);
	}
	
	/**
	 * When a module is installed it will install dependencies allong
	 * This boolean prevents an endless loop installation
	 * 
	 * @var boolean 
	 */
	public function dontInstallDatabase() {
		$this->dontInstallDatabase = true;
	}
	
	/**
	 * Return the module information retreived from the module XML file.
	 * 
	 * @return array
	 */
	public function getModuleInformation(){
		return $this->manager()->getModuleInformation();
	}
	
	
	/**
	 * Get the icon URL
	 * 
	 * Defaults to Resources/icon.svg. If it doesn't exist it defaults to GO/Core/Resources/module-icon-default.svg
	 * 
	 * @return string URL
	 */
	public function getIcon() {
		
		$iconFile = (new IFW\Fs\Folder($this->manager()->getPath()))->getFile('Resources/icon.svg');
		
		if($iconFile->exists()) {		
			return GO()->getRouter()->buildUrl('resources/'.urlencode($this->name).'/icon.svg');
		}else
		{
			return GO()->getRouter()->buildUrl('resources/'.urlencode(CoreModule::class).'/module-icon-default.svg');
		}
	}

	
	
	/**
	 * Get the module manager file
	 * 
	 * @return InstallableModule
	 */
	public function manager(){		
		
		if(!class_exists($this->name)){
			throw new \Exception("Module ".$this->name." is not found!");
		}
		
		$manager = new $this->name;
		$manager->setRecord($this);
		
		return $manager;
	}
	
//	public function isAvailable() {
//		return class_exists($this->name);
//	}
	
	private function checkConflicts() {		
		$installedModulesNames = [];
		$modules = Module::find();		
		foreach($modules as $module) {			
			
			if(class_exists($module->name)) {
				$installedModulesNames[] = $module->name;

				if(in_array($this->name, $module->manager()->conflicts())) {
					$this->setValidationError('conflicts', \IFW\Validate\ErrorCode::CONFLICT, $module->name.' conflicts with '.$this->name);

					return false;
				}
			}
		}
		
		foreach ($this->manager()->conflicts() as $moduleName) {
			if(in_array($moduleName, $installedModulesNames)){
				$this->setValidationError('conflicts',  \IFW\Validate\ErrorCode::CONFLICT, $module->name.' conflicts with '.$this->name);
				return false;
			}
		}
		
		return true;
	}
	
	protected function internalValidate() {		
		
		if(!is_a($this->name, InstallableModule::class, true)) {
			$this->setValidationError('name', \IFW\Validate\ErrorCode::NOT_FOUND, $this->name.' is not an installable module');
			
			return false;
		}
		
		$this->checkConflicts();		
		return parent::internalValidate();
	}

	protected function internalSave() {				
		if(!parent::internalSave()){
			return false;
		}
		
		if($this->isNew() && !$this->dontInstallDatabase) {			
			$depends = $this->manager()->getRecursiveDependencies();				
			$this->runModuleUpdates(false, $depends);
			
			if(!$this->manager()->install($this)) {
				return false;
			}
		}
		
		//make sure cache is up to date. router routes are cached for example.
		if($this->isNew() || $this->isModified('deleted') || $this->isModified('version')) {
			GO()->getCache()->flush();
			
		}
		
		if($this->isNew()) {
			$modules = GO()->getModules();
			$modules[] = $this->name;
		}
		
		return true;
	}
	
	
	/**
	 * Run all module upgrades of the given modules
	 * 
	 * @param array $moduleManagers
	 * @throws Exception
	 */
	public static function runModuleUpdates($skipFirstError = false, array $moduleManagers = null){
		
		$updates = self::collectModuleUpgrades($moduleManagers);
		
		foreach($updates as $update){
			
			$file = $update[1];
			$moduleManagerClass = $update[0];

			$module = Module::find(['name' => $moduleManagerClass])->single();
			if(!$module) {

				GO()->debug("Installing dependency module '".$moduleManagerClass."'");

				$module = new Module();
				$module->name = $moduleManagerClass;
				$module->dontInstallDatabase();	
				$module->save();
			}				

			GO()->debug("Running installation file '".$file->getPath()."'");

			if ($file->getExtension() === 'php') {
				self::runScript($file, $skipFirstError);
			} else {
				self::runQueries($file, $skipFirstError);
			}
			
			$module->version++;
			
			GO()->debug('Saving module '.implode(',', $module->getModified()));
			
			if(!$module->save()) {
				throw new \Exception("Could not save module ".$module->name.". Validation errors: ".var_export($module->getValidationErrors(), true));
			}
		}	 
	}
	
	private static function runScript(\IFW\Fs\File $file, &$skipFirstError) {
		try {
			require($file->path());
		} catch (\Exception $e) {
			if (!$skipFirstError) {
				$msg = "An exception ocurred in upgrade file " . $file->getPath() . 
								"\nIf you're a developer, you might need to skip this file "
								. "because you already applied the changes to your database. "
								. "Empty the file temporarily and rerun the upgrade.\n\n"
							. "PDO ERROR: \n\n" . $e->getMessage();
				throw new \Exception($msg);
			}else
			{
				GO()->debug("Skipping error: ".$e->getMessage());
				$skipFirstError = false;
			}
			
		}
	}

	private static function runQueries(\IFW\Fs\File $file, &$skipFirstError) {
		$queries = Utils::getSqlQueries($file);
		foreach ($queries as $query) {
			try {
				IFW::app()->getDbConnection()->query($query);
			} catch (\Exception $e) {
				if (!$skipFirstError) {
					$msg = "An exception ocurred in upgrade file " . $file->getPath() . 
									"\nIf you're a developer, you might need to skip this file "
									. "because you already applied the changes to your database."
									. "Empty the file temporarily and rerun the upgrade.\n\n"
									. "PDO ERROR: \n\n" . $e->getMessage();
					throw new \Exception($msg);
				}
				$skipFirstError = false;
			}
		}
	}
	
	
	/**
	 * Get all update files of the given modules sorted by date.
	 * 
	 * @return File[]
	 */
	private static function collectModuleUpgrades(array $moduleManagers = null) {
		
		if(!isset($moduleManagers)){			
			$moduleManagers = [];			
			$modules = Module::find();
			foreach ($modules as $module) {
				if(!$module->exists()) {
					GO()->debug("Disabling module '".$module->name."' because it does not exist in the code");
					$module->delete();					
				}else
				{
					$moduleManagers[] = $module->name;
				}
			}
		}
		
		$updates = [];
		
		foreach ($moduleManagers as $moduleManagerClass) {
			
			$manager = new $moduleManagerClass;
			$modUpdates = $manager->databaseUpdates();

			foreach ($modUpdates as $updateFile) {
				$suffix = "";

				while (isset($updates[$updateFile->getName() . $suffix])) {
					$suffix++;
				}

				$updates[$updateFile->getName() . $suffix] = [$moduleManagerClass, $updateFile];
			}
		}
		
		ksort($updates);

		return array_values($updates);
	}
	
	/**
	 * Checks if the module exists in the code base
	 * 
	 * @return boolean
	 */
	public function exists() {
		return class_exists($this->name);
	}
	
	protected function internalDelete($hard) {	
		
		//check dependencies
		$modules = Module::find();		
		foreach($modules as $module) {			
			if($module->exists() && in_array($this->name, $module->manager()->depends()) ){
				$this->setValidationError('depends', \IFW\Validate\ErrorCode::DEPENDENCY_NOT_SATISFIED, $module->name.' depends on '.$this->name);
				return false;
			}
		}
		
		if($hard && !$this->manager()->uninstall($this)) {
			$this->setValidationError('deleted', \IFW\Validate\ErrorCode::DELETE_RELATION_FAILED, 'Uninstall returned false');
			return false;
		}
		
		return parent::internalDelete($hard);
	}
	
	
	public function getPermissionTypes() {
		return $this->getPermissions()->getPermissionTypes();
	}
	
//	public function getPermissions() {
//		
//	}
	
	protected static function internalGetPermissions() {
		return new ModulePermissions();
	}
//	
//	public static function internalGetPermissions() {
//		return $this->manager()->getPermissions();
//	}
	
//	public function permissionTypes(){		
//		$types = parent::permissionTypes();		
//		$reflectionClass = new ReflectionClass($this->name);
//		foreach($reflectionClass->getConstants() as $name => $value){
//			if(substr($name, 0, 11) == 'PERMISSION_') {
//				$types[strtolower(substr($name,11))] = $value;
//			}
//		}		
//		
//		return $types;		
//	}
	
//	public function setOwnerPermissions() {
//		return true;
//	}
//	
}