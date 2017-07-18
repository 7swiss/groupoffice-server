<?php
namespace IFW\Orm\Utils;

use IFW\Db\Expression;
use IFW\Db\Query;
use IFW\Orm\Record;
use function GO;

/**
 * SortOrder class
 * 
 * Utility to use a column for manual sorting.
 * 
 * 1. Create a "sortOrder" column for your {@see Record}
 * 2. Implement this code in the record:
 *	```
 *	protected function internalSave() {
 *		
 *		\IFW\Orm\Utils\SortColumn::sort($this);
 * 
 *		return parent::internalSave();
 *  }
 * ```
 * 
 * 3. Now you can set the new sort order to a new position in the list. 
 * 4. In the controller you should sort the column ASC:
 *	```
 *		$query = (new Query())				
 *			->orderBy(['sortOrder' => "ASC", 'id' => "DESC"]);
 *	```
 * 
 */
class SortColumn {
	
	/**
	 *
	 * @var Record 
	 */
	private $record;
	
	/**
	 *
	 * @var string 
	 */
	private $columnName;
	
	private function __construct(Record $record, $columnName = 'sortOrder') {
		$this->record = $record;
		$this->columnName = $columnName;		
	}
	
	/**
	 * Reorder records when the sort column is modified
	 * 
	 * @param Record $record
	 * @param string $columnName
	 */
	public static function sort(Record $record, $columnName = 'sortOrder') {
		$self = new self($record,$columnName);
		$self->handleSortOrder();
	}
	
	private function handleSortOrder() {
		if($this->record->isNew()) {
			$this->nextSortOrder();
		} elseif($this->record->isModified('sortOrder')) {
			$this->modifySortOrder();
		}
	}
	
	private function nextSortOrder() {
		$max = (new Query)
						->fetchSingleValue('MAX(sortOrder)')
						->from($this->record->tableName())
						->createCommand()
						->execute()->fetch();
		
		$this->record->sortOrder = $max + 1;
	}
	private function modifySortOrder() {
		$old = $this->record->getOldAttributeValue('sortOrder');
		
		$isMovedUp = $this->record->sortOrder < $old;
		
		if($isMovedUp) {
			//open up hole down			
//			$sql = "UPDATE ".$this->tableName()." SET sortOrder = sortOrder -1 WHERE sortOrder > :old AND sortOrder <= :new";
			
			//eg 1,2,3,4 => 1,4,2,3 then sortOrder 4 is modified to 2
			GO()->getDbConnection()->createCommand()
							->update(
											$this->record->tableName(), 
											new Expression('sortOrder = sortOrder + 1'), 
											(new Query)
												->where(['>=', ['sortOrder' => $this->record->sortOrder]])
												->andWhere(['<', ['sortOrder' => $old]])
											)->execute();
			
		} else
		{
			
			//eg 1,2,3,4 => 2,3,1,4  then sortOrder 1 is modified to 3
			
			//open up hole up
			GO()->getDbConnection()->createCommand()
							->update(
											$this->record->tableName(), 
											new Expression('sortOrder = sortOrder - 1'), 
											(new Query)
												->where(['>', ['sortOrder' => $old]])
												->andWhere(['<=', ['sortOrder' => $this->record->sortOrder]])
											)->execute();
		}
	}
}