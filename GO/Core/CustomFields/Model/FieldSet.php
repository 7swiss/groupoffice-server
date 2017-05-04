<?php
namespace GO\Core\CustomFields\Model;

use IFW\Auth\Permissions\ReadOnly;
use IFW\Orm\Record;
use IFW\Orm\Query;
use IFW\Orm\Relation;


/**
 * FieldSet model
 * 
 *
 *
 * 
 * @property Field[] $fields
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class FieldSet extends Record{
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $sortOrder = 0;

	/**
	 * 
	 * @var string
	 */							
	public $modelName;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	protected static function defineRelations() {		
			self::hasMany('fields', Field::class, ['id' => 'fieldSetId'])
				->setQuery((new Query())->orderBy(['sortOrder' => 'ASC']));		
	}
	
	protected static function internalGetPermissions() {
		return new ReadOnly();
	}
	
	/**
	 * Get's the table name to store custom fields in.
	 * 
	 * @param string
	 */
	public function customFieldsTableName(){		
		return call_user_func([$this->modelName, 'tableName']);		
	}
	
	public function internalValidate() {
		
		if(!class_exists($this->modelName) || !is_subclass_of($this->modelName, CustomFieldsRecord::class)){
			$this->setValidationError('model', 'noCustomFieldsRecord', ['modelName' => $this->modelName]);
		}
		
		return parent::internalValidate();
	}
	/**
	 * Find a fieldset by record class name and name or create it if it doesn't exist
	 * 
	 * @example
	 * ```
	 * $fieldSet = FieldSet::findOrCreate(\GO\Modules\GroupOffice\Contacts\Model\CustomFields::class, 'Test fieldset');
	 * ```
	 * 
	 * @param string $recordClassName Must be a class that extends {@see CustomFieldsRecord}
	 * @param string $name
	 * @return \GO\Core\CustomFields\Model\FieldSet
	 * @throws \Exception
	 */
	
	public static function findOrCreate($recordClassName, $name) {
		$fieldset = \GO\Core\CustomFields\Model\FieldSet::find(['modelName' => $recordClassName, 'name' => $name])->single();
				
		if(!$fieldset) {
			$fieldset = new \GO\Core\CustomFields\Model\FieldSet();
			$fieldset->modelName = $recordClassName;
			$fieldset->name = $name;
			if(!$fieldset->save()) {
				throw new \Exception("Could not save fieldset: ".var_export($fieldset->getValidationErrors(), true));
			}
		}
		
		return $fieldset;
	}
}