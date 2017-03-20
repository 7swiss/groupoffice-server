<?php

namespace IFW\Orm;

use Exception;

class Utils {

	/**
	 * Duplicate a record
	 * 
	 * ```
	 *    $terms = Terms::findByPk($termsId);
	 * 
	 * 		if (!$terms) {
	 * 			throw new NotFound();
	 * 		}
	 * 
	 *		$name = \IFW\Orm\Utils::findUniqueValue($terms->tableName(), 'name', $terms->name);
	 * 
	 * 		$duplicate = \IFW\Orm\Utils::duplicate($terms, ['name' => $name]);
	 * 		foreach($terms->actions as $action) {
	 * 			\IFW\Orm\Utils::duplicate($action, ['termsId' => $duplicate->id]);
	 * 		}
	 * 		
	 * 		foreach($terms->templateMessages as $tm) {
	 * 			\IFW\Orm\Utils::duplicate($tm, ['termsId' => $duplicate->id]);
	 * 		}
	 * ```
	 * 
	 * @param \IFW\Orm\Record $record
	 * @param type $nameColumn
	 * @param type $query
	 * @return \IFW\Orm\Record
	 * @throws Exception
	 */
	public static function duplicate(\IFW\Orm\Record $record, $values = []) {

		$cls = $record->getClassName();
		//create new record instance
		$duplicate = new $cls;
		
		//copy database properties
		$duplicate->setValues(array_merge($record->toArray($record->getTable()->getColumnNames()), $values));
		
		//set auto increments to null
		foreach ($record->getTable()->getColumns() as $column) {
			if($column->autoIncrement) {
				$duplicate->{$column->name} = null;
			}
		}

		if(!$duplicate->save()) {
			throw new \Exception("Could not duplicate record");
		}

		return $duplicate;
	}
	
	/**
	 * Find a unique value for a string column by appending a number.
	 * 
	 * For example "Some name" becomes "Some name (1)". This function is useful
	 * for duplicating records.
	 * 
	 * @param string $tableName
	 * @param string $columnName
	 * @param string $value
	 * @param Query $query
	 * @return string
	 */
	public static function findUniqueValue($tableName, $columnName, $value, $query = null) {
		$current = $value. ' (1)';	

		$x = 1;
		while (Query::normalize($query)
						->withDeleted()
						->select($columnName)
						->from($tableName)
						->where([$columnName => $current])
						->createCommand()
						->execute()
						->rowCount() > 0) {

			$current = $value . ' (' . $x . ')';
			$x++;
		}
		
		return $current;
	}

}
