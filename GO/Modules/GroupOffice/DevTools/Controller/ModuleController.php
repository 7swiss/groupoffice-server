<?php

namespace GO\Modules\GroupOffice\DevTools\Controller;

use Exception;
use GO\Core\Controller;
use GO\Core\CustomFields\Model\CustomFieldsRecord;
use IFW\Db\Column;
use IFW\Fs\Folder;
use IFW\Orm\Record;
use IFW\Util\ClassFinder;

/**
 * 10s vs 6s in new record.
 * 
 * RecordTest: 
 */
class ModuleController extends Controller {

	private function columnToPhpType($column) {
		switch ($column->dbType) {
			case 'int':
			case 'tinyint':
			case 'bigint':
				if ($column->length === 1) {
					//Boolean fields in mysql are listed at tinyint(1);
					return "bool";
				} else {
					// Use floatval because of ints greater then 32 bit? Problem with floatval that ints will set as modified attribute when saving.
					return "int";
				}

			case 'float':
			case 'double':
			case 'decimal':
				return "double";

			case 'date':
			case 'datetime':
				return "\IFW\Util\DateTime";

			default:
				return "string";
		}
	}

	private function getDefaultValue($column) {
		if (!isset($column->default)) {
			return "";
		}

		return " = " . var_export($column->default, true);
	}

	private function createModuleFile($folder, $namespace) {
		$moduleFile = $folder->getFile('Module.php');
		if (!$moduleFile->exists()) {


			$year = date('Y');

			$data = <<<EOD
<?php
namespace $namespace;
							
use GO\Core\Modules\Model\InstallableModule;
/**						
 * @copyright (c) $year, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Module extends InstallableModule {

}
EOD;

			$moduleFile->putContents($data);
		}
	}

	/**
	 * mschering@mschering-UX31A:/var/www/groupoffice-server/GO/Modules/GroupOffice/Tasks$ ../../../../bin/groupoffice devtools/module/init --tablePrefix=tasks
	 * 
	 * @param type $tablePrefix
	 */
	protected function actionInit($tablePrefix) {

//		$className = \GO\Modules\GroupOffice\DevTools\Model\RecordTest::class;
//		$className = \GO\Core\Users\Model\User::class;
//		$this->convertClass($className);
//		
//		exit();

		$folder = new Folder(getcwd());
		$folder->getFolder('Model')->create();
		$folder->getFolder('Controller')->create();
		



		$namespace = $folder->getPath();
		$namespace = substr($namespace, strpos($namespace, 'GO'));
		$namespace = str_replace('/', '\\', $namespace);

		
		if(strpos($folder, 'GO\Core') === false) {
			$this->createModuleFile($folder, $namespace);
			$this->createModuleXML($namespace, $folder);	

			$dbFolder = $folder->getFolder('Install/Database');

			if(!$dbFolder->exists()) {
				$dbFolder->create();
				$sqlFile = $dbFolder->getFile(date('Ymd-Hm').'.sql');
				$sqlFile->putContents('-- This is the first database installation/patch file. The name should be in the format Ymd-Hm.sql');
			}
		}


		$result = GO()->getDbConnection()->query("SHOW TABLES");

		while ($record = $result->fetch(\PDO::FETCH_NUM)) {
			if (strpos($record[0], $tablePrefix . '_') === 0) {
				$this->tableToRecord($folder, $namespace, $tablePrefix, $record[0]);
			}
		}
	}

		private function createModuleXML($namespace, Folder $folder) {
			
			$file = $folder->getFile('Module.xml');
			
			if($file->exists())
			{
				return;
			}
			
			$moduleName = $folder->name;
			
			$data = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<module>
	<author>Intermesh B.V.</author>
	<email>info@intermesh.nl</email>
	<version>0.1</version>
	<languages>
		
		 <language type="en">
			<name>$moduleName</name>
			<shortDescription>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc iaculis scelerisque suscipit.
			</shortDescription>
			<longDescription>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc iaculis scelerisque suscipit. 
				Sed malesuada elit vel leo sollicitudin malesuada. In velit arcu, dignissim non nunc ut, molestie hendrerit magna. 
				Fusce sit amet blandit leo, quis tristique nibh. Mauris in tristique est. 
				Duis posuere, nisl nec pharetra finibus, magna massa elementum sem, a tristique tellus enim sit amet risus. 
				Maecenas quis odio eget lorem scelerisque tristique. Ut sit amet est ultrices, varius sapien quis, dignissim risus. 
				Sed lacinia vehicula sapien, non ultricies tellus bibendum ut. Proin non condimentum purus, sit amet aliquet turpis. 
				Aenean et lacus ante. Fusce vulputate sed felis ut interdum. In erat est, molestie sed nulla vitae, sollicitudin auctor diam. 
				In molestie sodales magna, vel viverra magna. 
				Morbi semper nisl ut erat cursus tempor. 
				Sed at sodales nisl, eget molestie neque. 
			</longDescription>
			<images>
				<image src="https://cdn2.iconfinder.com/data/icons/crystalproject/crystal_project_256x256/filesystems/blockdevice.png" alt="EN"></image>
				<image src="http://www.smartapps.com.br/a/libs/img/block-apps.png" alt="EN"></image>
			</images>
		</language>
							
		<language type="nl">
			<name>$moduleName</name>
			<shortDescription>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc iaculis scelerisque suscipit.
			</shortDescription>
			<longDescription>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc iaculis scelerisque suscipit. 
				Sed malesuada elit vel leo sollicitudin malesuada. In velit arcu, dignissim non nunc ut, molestie hendrerit magna. 
				Fusce sit amet blandit leo, quis tristique nibh. Mauris in tristique est. 
				Duis posuere, nisl nec pharetra finibus, magna massa elementum sem, a tristique tellus enim sit amet risus. 
				Maecenas quis odio eget lorem scelerisque tristique. Ut sit amet est ultrices, varius sapien quis, dignissim risus. 
				Sed lacinia vehicula sapien, non ultricies tellus bibendum ut. Proin non condimentum purus, sit amet aliquet turpis. 
				Aenean et lacus ante. Fusce vulputate sed felis ut interdum. In erat est, molestie sed nulla vitae, sollicitudin auctor diam. 
				In molestie sodales magna, vel viverra magna. 
				Morbi semper nisl ut erat cursus tempor. 
				Sed at sodales nisl, eget molestie neque. 
			</longDescription>
			<images>
				<image src="https://cdn2.iconfinder.com/data/icons/crystalproject/crystal_project_256x256/filesystems/blockdevice.png" alt="NL"></image>
					<image src="http://www.smartapps.com.br/a/libs/img/block-apps.png" alt="NL"></image>					
			</images>
		</language>
		
	</languages>
</module>					
							
EOD;
			
			$file->putContents($data);
			
			
			
		}


	private function tableToController($namespace, $recordName, Folder $folder) {

		$file = $folder->getFolder('Controller')->getFile($recordName . 'Controller.php');


		if (!$file->exists()) {


			$replacements = [
					'namespace' => $namespace,
					'modelLowerCase' => lcfirst($recordName),
					'modelUcfirst' => $recordName
			];

			$controllerTpl = file_get_contents(__DIR__ . '/../Controller.tpl');

			foreach ($replacements as $key => $value) {
				$controllerTpl = str_replace('{' . $key . '}', $value, $controllerTpl);
			}
			$file->putContents($controllerTpl);
		}
	}

	private function tableToRecord(Folder $folder, $namespace, $tablePrefix, $tableName) {

		$recordName = \IFW\Util\StringUtil::upperCamelCasify(str_replace($tablePrefix . '_', '', $tableName));

		

		$file = $folder->getFolder('Model')->getFile($recordName . '.php');


		if (!$file->exists()) {
			
			
			$this->tableToController($namespace, $recordName, $folder);

			$year = date('Y');

			$data = <<<EOD
<?php
namespace $namespace\Model;
						
use GO\Core\Orm\Record;
						
/**
 * The $recordName record
 *
 * @copyright (c) $year, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class $recordName extends Record{


}
EOD;
			$file->putContents($data);
		}


		$className = $namespace . '\\Model\\' . $recordName;
		$this->convertClass($className, $file);
	}

	protected function convertClass($className, $file) {
		
		if(is_a($className, CustomFieldsRecord::class, true)) {
			echo "Skipping custom fields record ".$className."\n";
			return;
		}

		echo "Converting $className\n";


		$columns = $className::getTable()->getColumns();

		/* @var $columns Column */


		$source = $file->getContents();

		$vars = '';

		foreach ($columns as $column) {
			
			//skip comment commentId
			if($column->name == 'commentId' && is_a($className, \GO\Core\Comments\Model\Comment::class, true)) {
				continue;
			}

			//check if property is already defined
			if (preg_match('/(protected|public)\s+\$' . preg_quote($column->name, '/') . '[;\s]/', $source)) {

//				echo $column->name . " found\n";
				continue;
			}

			$vars .= <<<EOD
	/**
	 * {$column->comment}
	 * @var {$this->columnToPhpType($column)}
	 */							
	public \${$column->name}{$this->getDefaultValue($column)};


EOD;
		}

		//find position to insert properties
		preg_match('/class .*\{\s*\n/', $source, $matches, PREG_OFFSET_CAPTURE);
		$pos = $matches[0][1] + strlen($matches[0][0]);

		$source = substr($source, 0, $pos) . $vars . substr($source, $pos);

		$file->putContents($source);
	}

}
