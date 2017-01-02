<?php

namespace GO\Core\CustomFields\Model;

use Exception;
use GO\Core\CustomFields\Filter\CheckboxFilter;
use GO\Core\CustomFields\Filter\NumberFilter;
use GO\Core\CustomFields\Filter\SelectFilter;
use IFW;
use IFW\Auth\Permissions\ReadOnly;
use IFW\Data\Filter\FilterCollection;
use IFW\Db\Columns;
use IFW\Orm\Query;
use IFW\Orm\Record;


/**
 * Field model
 * 
 * Defines a custom field
 *
 * @property array $data Extra options for the custom field eg. ["maxLength": 50, "selectOptions": ['option 1', 'option 2']]
 * 
 * @property FieldSet $fieldSet
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Field extends Record {

	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $fieldSetId;

	/**
	 * 
	 * @var int
	 */							
	public $sortOrder = 0;

	/**
	 * One of the TYPE_* constants
	 * @var string
	 */							
	public $type;

	/**
	 * The name presented to the user.
	 * @var string
	 */							
	public $name;

	/**
	 * The name of the database column
	 * @var string
	 */							
	public $databaseName;

	/**
	 * 
	 * @var string
	 */							
	public $placeholder = '';

	/**
	 * 
	 * @var bool
	 */							
	public $required = false;

	/**
	 * 
	 * @var string
	 */							
	public $defaultValue = '';

	/**
	 * 
	 * @var string
	 */							
	public $_data;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * 
	 * @var bool
	 */							
	public $filterable = false;

	const TYPE_TEXT = "text"; // and textarea
	const TYPE_CHECKBOX = "checkbox";
	const TYPE_SELECT = "select";
	const TYPE_DATE = "date"; // and datetime
	
	const TYPE_NUMBER = "number";

	protected static function defineRelations() {
		self::hasOne('fieldSet', FieldSet::class, ['fieldSetId' => 'id']);
		
		parent::defineRelations();
	}
	
	protected function init() {
		parent::init();
	
		if($this->isNew()) {
			$this->type = self::TYPE_TEXT;
			$this->data = array(
					'maxLength' => 50,
					'multiline'=> false,
					'withTime' => false
					);
		}
	}
	
	protected static function internalGetPermissions() {
		return new ReadOnly();
	}

	protected function internalDelete($hard) {
		
		if(parent::internal($hard) && $hard) {
			//don't be strict in upgrade process
			GO()->getDbConnection()->getPdo()->query("SET sql_mode=''");

			$sql = "ALTER TABLE `" . $this->fieldSet->customFieldsTableName() . "` DROP `" . $this->databaseName . "`";

			try {
				GO()->getDbConnection()->getPdo()->query($sql);
			} catch (Exception $e) {
				trigger_error("Dropping custom field column failed with error: " . $e->getMessage());
			}

		//for cached database columns
			Columns::clearCache($this->fieldSet->modelName);
		}
		
		return true;
	}

//	private function _createTable() {
//		$sql = "CREATE TABLE IF NOT EXISTS `" . $this->fieldSet->customFieldsTableName() . "` (
//			`id` int(11) NOT NULL,		
//			PRIMARY KEY (`id`)
//		  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
//
//		return GO()->dbConnection()->getPdo()->query($sql);
//	}
	
	private function _getTypeSql(){
		switch($this->type){
			
			case self::TYPE_DATE:
				if($this->data['withTime']) {
					$sql = "DATE";
				} else {
					$sql = "DATE NULL";
				}
				if(!empty($this->defaultValue)){
					$sql .= " DEFAULT ".GO()->getDbConnection()->getPDO()->quote($this->defaultValue);
				}
				return $sql;
			
			case self::TYPE_CHECKBOX:			
				return "BOOLEAN NOT NULL DEFAULT '".intval($this->defaultValue)."'";
			
			case self::TYPE_TEXT:	
				
				if($this->data['multiline']){
					
					return "TEXT NULL";
					
				} else {
					$data = $this->data;
					if(!isset($data['maxLength'])){
						$data['maxLength'] = 50;
						$this->data = $data;
					}				
					return "VARCHAR(".$this->data['maxLength'].") NOT NULL DEFAULT ".GO()->getDbConnection()->getPDO()->quote($this->defaultValue);
				}
			case self::TYPE_SELECT:
				
				return "VARCHAR(".$this->_findLargestSelectOption().") NOT NULL DEFAULT ".GO()->getDbConnection()->getPDO()->quote($this->defaultValue);
			case self::TYPE_NUMBER:
				
				$sql = "DOUBLE NULL";
				
				if(!empty($this->defaultValue)){
					$sql .= " DEFAULT ".GO()->getDbConnection()->getPDO()->quote($this->defaultValue);
				}
				
				return $sql;
			
			default:
				throw new Exception("Not implemented!?");				
		}
	}
	
	/**
	 * To determine the size for the varchar() field with select options
	 * @return int
	 */
	private function _findLargestSelectOption(){
		$max = 0;
		foreach($this->data['options'] as $option){
			$l = strlen($option);
			if($l > $max){
				$max = $l;
			}
		}
		
		return $max;
	}
	
	private function _hasOption($value){		
		return in_array($value, $this->data['options']);
	}
	
	public function internalValidate() {
		
		if(!parent::internalValidate()) {
			return false;
		}
		
		if(IFW\Db\Utils::columnExists($this->fieldSet->customFieldsTableName(), $this->databaseName)){
			$this->setValidationError('databaseName', 'columnexists');
			return false;
		}
		
		switch($this->type) {
			case self::TYPE_SELECT:
		
				if(!isset($this->data['options'])){
					$this->setValidationError('data', 'noSelectOptions');
				}
				if(!empty($this->defaultValue) && !$this->_hasOption($this->defaultValue)){
					$this->setValidationError('defaultValue', 'defaultNotASelectOption');
				}
				break;
				
			case self::TYPE_TEXT:
				
				if($this->data['multiline'] && !empty($this->defaultValue)) {
					$this->setValidationError('defaultValue', 'textCantHaveDefaultValue');
				}
				
				break;
		}
		
		return true;
	}
	
	public static function defineSortOrderCriteria() {
		return ['fieldSetId'];
	}
	
	protected function internalSave() {
		
//		try {
			$this->alterDatabase();
//		}
//		catch (Exception $e) {
//			GO()->debug($e->getMessage());
//			$this->setValidationError('databaseName', $e->getMessage());
//			return false;
//		}
		
		return parent::internalSave();
	}
	
//	public function save() {
//		
//		
//		$startTrans = $this->isModified(['databaseName','defaultValue', '_data','type']) && !GO()->dbConnection()->inTransaction();
//		
//		if($startTrans){
//			GO()->dbConnection()->beginTransaction();
//		}
//		
//		try {
//			$this->_alterDatabase();
//			
//			$success = parent::save();
//		
//		} catch (Exception $ex) {
//			
//			if($startTrans){
//				GO()->dbConnection()->rollBack();
//			}
//			
//			$this->setValidationError('databaseName', $ex->getMessage());
//			
//			GO()->debug($ex->getMessage());
//			
//			return false;
//		}
//		
//		if($startTrans){
//			GO()->dbConnection()->commit();
//		}
//		
//		
//		return $success;
//	}
	
	public function getData(){
		return json_decode($this->_data, true);
	}
	
	public function setData(array $data){
		$this->_data = json_encode($data);	
	}	

	/**
	 * Transactions stop working when alter statement is made! 
	 */
	private function alterDatabase() {
		
		if($this->isModified(['databaseName','defaultValue', '_data','type'])){
			
			
			GO()->debug("TRANSACTION NO LONGER WORKING BECAUSE OF ALTER STATEMENT");

			$table = $this->fieldSet->customFieldsTableName();

			if ($this->isNew()) {
				$sql = "ALTER TABLE `" . $table . "` ADD `" . $this->databaseName . "` " . $this->_getTypeSql() . ";";
			} else {
				$tableName = $this->getOldAttributeValue('databaseName');
				if(!$tableName){
					$tableName = $this->databaseName;
				}
				$sql = "ALTER TABLE `" . $table . "` CHANGE `" . $tableName . "` `" . $this->databaseName . "` " . $this->_getTypeSql();
			}

//			echo $sql;

			//don't be strict in upgrade process
			GO()->getDbConnection()->getPdo()->query("SET sql_mode=''");

			if (!GO()->getDbConnection()->getPdo()->query($sql)) {
				throw new Exception("Could not create custom field");
			}

			//for cached database columns
			$this->fieldSet->getColumns()->clearCache();
		}
	}
	
	/**
	 * Add filters for custom fields to a filtercollection
	 * 
	 * All fields that have "filterable" enabled will be added as a filter.
	 * 
	 * @param string $customfieldsModelName eg. "GO\Modules\Contacts\Model\ContactCustomFields"
	 * @param FilterCollection $collection
	 */
	public static function addFilters($customfieldsModelName, FilterCollection $collection) {
		$fields = self::find((new Query())->where(['filterable' => true, 'fieldSet.modelName' => $customfieldsModelName])->orderBy(['sortOrder'=>'ASC']));
		
		foreach($fields as $field) {
			switch ($field->type) {
				case self::TYPE_CHECKBOX:
					$filter = $collection->addFilter(CheckboxFilter::class);
					$filter->setField($field);
					break;
				
				case self::TYPE_NUMBER:
					$filter = $collection->addFilter(NumberFilter::class);
					$filter->setField($field);
					break;
				
				case self::TYPE_SELECT:
					$filter = $collection->addFilter(SelectFilter::class);
					$filter->setField($field);
					break;
			}
		}
	}

}
