<?php
namespace GO\Modules\GroupOffice\DevTools\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\DevTools\Model\RecordTest;
use GO\Modules\GroupOffice\DevTools\Model\Record;
use function GO;

/**
 * 10s vs 6s in new record.
 * 
 * RecordTest: 
 */
class RecordTestController extends Controller {
	protected function actionBenchmark() {
//		
		GO()->getDbConnection()->query('TRUNCATE TABLE dev_tools_record_test');
		GO()->getDbConnection()->query('TRUNCATE TABLE dev_tools_record');
		
		
		
		$start = GO()->getDebugger()->getMicroTime();
		for($i = 0, $l=10000;$i<$l;$i++) {
			$record = new RecordTest();
			$record->name = 'name_'.$i;
			$record->save();
		}
		
		$query = new \IFW\Orm\Query;
		$query->fetchMode(\PDO::FETCH_CLASS, RecordTest::class, [false]);
		
		foreach(RecordTest::find($query) as $record) {
			$record->name .= '_1';
			$record->save();
		}
		
//		var_dump($record);
		
		$end = GO()->getDebugger()->getMicroTime();
		
		echo memory_get_peak_usage()."\n";		
		echo "RecordTest: ". ($end - $start)."ms\n";
		
		
		
		
		
		
		$start = GO()->getDebugger()->getMicroTime();
		for($i = 0, $l=10000;$i<$l;$i++) {
			$record = new Record();
			$record->name = 'name_'.$i;
			$record->save();
		}
	
		
		foreach(Record::find() as $record) {
			$record->name .= '_1';
			$record->save();
		}
		
//		var_dump($record);
		
		$end = GO()->getDebugger()->getMicroTime();		
		
		echo memory_get_peak_usage()."\n";	
		echo "Record: ". ($end - $start)."ms\n";
		
	}
	
	
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
				return "\DateTime";
				
			default:
				return "string";
		}
	}
	
	private function getDefaultValue($column) {
		if(!isset($column->default)) {
			return "";
		}
		
		return " = ".var_export($column->default, true);
	}
	
	protected function actionConvert() {
		
//		$className = \GO\Modules\GroupOffice\DevTools\Model\RecordTest::class;
//		$className = \GO\Core\Users\Model\User::class;
//		$this->convertClass($className);
//		
//		exit();
		
		
		
		$classFinder = new \IFW\Util\ClassFinder();
		$records = $classFinder->findByParent(\IFW\Orm\Record::class);
		
		foreach($records as $recordClassName) {			
			//Custom field records must work in the old way.
			if(is_a($recordClassName, \GO\Core\CustomFields\Model\CustomFieldsRecord::class, true)) {
				continue;
			}
			
			try {
				$this->convertClass($recordClassName);
			}catch (\Exception $e) {
				echo $e->getMessage()."\n";
			}
		}
	}
	
	protected function convertClass($className) {
		
		echo "Converting $className\n";
		
		
		$classFinder = new \IFW\Util\ClassFinder();
		$file = $classFinder->classNameToFile($className);
		
		
		$columns = $className::getColumns();
		
		/* @var $columns \IFW\Db\Column */
		
	
		$source = $file->getContents();
		
		$vars = '';
		
		foreach($columns as $column) {
			
			//check if property is already defined
			if(preg_match('/(protected|public)\s+\$'. preg_quote($column->name, '/').'[;\s]/', $source)){
				
				echo $column->name." found\n";
				continue;
			}
			
			//check if property should be protected or public
			$visibiliy = preg_match('/\$this->(get|set)Attribute\((\'|")'.preg_quote($column->name, '/').'/', $source) ? 'protected' : 'public';
			
			//Get comment from @property $name doctag
			$comment = '';
			if(preg_match('/.*property.*\$'.preg_quote($column->name, '/').'(.*)\n/', $source, $matches)) {
				$source = str_replace($matches[0], "", $source);
				
				$comment = trim($matches[1]);
			}
			
			//fall back on db column comment
			if(empty($comment)){
				$comment = $column->comment;
			}		

			$vars .= <<<EOD
	/**
	 * {$comment}
	 * @var {$this->columnToPhpType($column)}
	 */							
	$visibiliy \${$column->name}{$this->getDefaultValue($column)};


EOD;
	
	
			if($visibiliy =='protected') {
				$source = $this->replaceGetSetAttribute($source, $column);
			}
			
		}
		
		//find position to insert properties
		preg_match('/class .*\{\s*\n/', $source, $matches, PREG_OFFSET_CAPTURE);
		$pos = $matches[0][1]+strlen($matches[0][0]);
		
		$source = substr($source, 0,$pos).$vars.substr($source, $pos);
		
		
//		echo $source;
		$file->putContents($source);
	}
	
	/**
	 * Replace set and getAttribute with the protected var assignments.
	 * 
	 * @param type $source
	 * @param \IFW\Db\Column $column
	 * @return type
	 */
	private function replaceGetSetAttribute($source, \IFW\Db\Column $column) {
		$source = preg_replace('/\$this->setAttribute\(\s*(\'|")'.preg_quote($column->name, '/').'(\'|")\s*,\s*([^\)]+)\)/', '\$this->'.$column->name." = $3", $source);
		
		$source = preg_replace('/\$this->getAttribute\(\s*(\'|")'.preg_quote($column->name, '/').'(\'|")\s*\)/', '\$this->'.$column->name, $source);
		return $source;
	}
}
