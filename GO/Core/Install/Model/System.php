<?php

namespace GO\Core\Install\Model;

use GO\Core\Users\Model\User;
use GO\Core\Modules\Model\InstallableModule;
use GO\Core\Modules\Model\Module;
use IFW;
use IFW\Data\Model;
use IFW\Db\Utils;
use IFW\Fs\File;
use IFW\Fs\Folder;
use IFW\Util\ClassFinder;
use PDOException;

class System extends Model {

	/**
	 * Check if the GroupOffice database has been installed
	 *
	 * @return boolean
	 */
	public static function isDatabaseInstalled() {
		try {
			$isDbInstalled = IFW::app()->getDbConnection()->getPDO() && Utils::tableExists('modules_module');
		} catch (PDOException $e) {
			$isDbInstalled = false;
		}

		return $isDbInstalled;
	}

	/**
	 * Installs the GroupOffice database
	 *
	 * @return boolean
	 */
	public function install() {

		if ($this->isDatabaseInstalled()) {
			throw new \Exception("The database was already installed");
		}

		\IFW\Auth\Permissions\Model::$enablePermissions = false;

		$this->setUtf8Collation();

		$this->runCoreUpdates();

		$admin = User::findByPk(1);
		\GO()->getAuth()->setCurrentUser($admin);

		


		//Install all modules that should auto install
		$cf = new ClassFinder();
		$modules = $cf->findByParent(InstallableModule::class);

		foreach ($modules as $moduleName) {
			$module = new $moduleName();
			if ($module->autoInstall()) {

				//could have been auto installed by dependency
				if (!Module::find(['name' => $module->getClassName()])->single()) {
					$moduleModel = new Module();
					$moduleModel->name = $module->getClassName();
					$moduleModel->save();
				}
			}
		}

		
		IFW::app()->reinit();
		
		$this->installCronJob();

		return true;
	}

	private function installCronJob() {
		$cronJob = new \GO\Core\Cron\Model\Job();
		$cronJob->module = null;
		$cronJob->name = 'Account sync service';
		$cronJob->cronClassName = \GO\Core\Accounts\Model\Account::class;
		$cronJob->method = 'syncAll';
		$cronJob->cronExpression = '*/5 * * * *';
		if(!$cronJob->save()) {
			throw new \Exception("Could not install cron job");
		}
	}

	private function setUtf8Collation() {
		//Set utf8 as collation default
		$sql = "ALTER DATABASE `" . \GO()->getDbConnection()->database . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
		\GO()->getDbConnection()->query($sql);
	}

	/**
	 * Run necessary upgrade patches
	 *
	 * @todo Show queries in response.
	 * @param boolean $skipFirstError Set to true if you know the first error
	 * encountered because you've already made the database changes yourself as
	 * developer
	 *
	 * @return boolean
	 */
	public function upgrade() {

		if (!$this->isDatabaseInstalled()) {
			throw new \Exception("The database is not installed");
		}

		GO()->getCache()->flush();
		IFW\Orm\Record::initRelations();

		$this->setUtf8Collation();

		$this->runCoreUpdates();
		Module::runModuleUpdates();


		GO()->getCache()->flush();

		return true;
	}

	private function runCoreUpdates() {

		$updates = $this->databaseUpdates();


		foreach ($updates as $file) {
			if ($file->getExtension() === 'php') {
				$this->runScript($file);
			} else {
				$this->runQueries($file);
			}

			$this->getInstallation()->dbVersion++;
			if (!$this->getInstallation()->save()) {
				throw new \Exception("Could not save installation");
			}
		}
	}

	private function runScript(File $file) {
		try {
			require($file->path());
		} catch (\Exception $e) {
	
			$msg = "An exception ocurred in upgrade file " . $file->getPath() . 
							"\nIf you're a developer, you might need to skip this file because"
							. " you already applied the changes to your database. "
							. "Empty the file temporarily and rerun the upgrade.\n\n"
							. "Exception: \n\n" . IFW\ErrorHandler::logException($e);
			throw new \Exception($msg);			
		}
	}

	private function runQueries(File $file) {
		$queries = Utils::getSqlQueries($file);
		foreach ($queries as $query) {
			try {
				IFW::app()->getDbConnection()->query($query);
			} catch (\Exception $e) {
				$msg = "An exception ocurred in upgrade file " . $file->getPath() . 
								"\nIf you're a developer, you might need to skip this file "
								. "because you already applied the changes to your database. "
								. "Empty the file temporarily and rerun the upgrade.\n\n"
						. "PDO ERROR: \n\n" . $e->getMessage()."\n\nQuery:\n".$query;
				throw new \Exception($msg);	
			}
		}
	}

	/**
	 *
	 * @var Installation
	 */
	private $installation;

	/**
	 *
	 * @return Installation
	 */
	private function getInstallation() {
		if (!isset($this->installation)) {
			$this->installation = Installation::find()->single();
			if (!$this->installation) {
				$this->installation = new Installation();
			}
		}

		return $this->installation;
	}

	/**
	 * Get update files.
	 * Can be SQL or PHP scripts.
	 *
	 * @return File[] The array has the filename date stamp as key so it can be sorted
	 */
	private function databaseUpdates() {

		$dbFolder = new Folder(dirname(dirname(__FILE__)) . '/Database');
		if (!$dbFolder->exists()) {
			return [];
		} else {

			$files = $dbFolder->getChildren(true, false);

			usort($files, function($file1, $file2) {
				return $file1->getName() > $file2->getName();
			});

			$version = $this->isDatabaseInstalled() ? $this->getInstallation()->dbVersion : 0;

			if (!empty($version)) {
				$files = array_slice($files, $version);
			}


			$regex = '/[0-9]{8}-[0-9]{4}\.(sql|php)/';
			foreach ($files as $file) {
				if (!preg_match($regex, $file->getName())) {
					throw new \Exception("The upgrade file '" . $dbFolder->getPath() . "/" . $file->getName() . "' is not in the right filename format. It should be YYYYMMDD-HHMM.sql or YYYYMMDD-HHMM.php");
				}
			}

			return $files;
		}
	}

}
