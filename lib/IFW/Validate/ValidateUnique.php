<?php

namespace IFW\Validate;

use IFW\Data\Model;
use IFW\Orm\Query;

/**
 * Checks if the attribute is unique. Can also validate it in combination with other columns
 * 
 * Do not set this yourself. Just define a unique key on the database and it will
 * be generated automatically. Can also validate it in combination with other columns.
 * 
 * If for some reason you can't do this you can set it yourself:
 * 
 * <p>eg. in ActiveRecord do:</p>
 * 
 * <code>
 * protected static function defineValidationRules() {
 * 	
 * 		self::getColumn('username')->required=true;
 * 		
 * 		return array(
 * 				new ValidateEmail("email"),
 * 				new ValidateUnique('email'),
 * 				new ValidateUnique('username'),
 *        new ValidatePassword('password', 'passwordConfirm') //Also encrypts it on success
 * 		);
 * 	}
 * </code>
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ValidateUnique extends Base {

	private $_relatedColumns = [];

	/**
	 * Validate a unique value of this column in combination with other columns.
	 * 
	 * @param array $relatedColumns
	 */
	public function setRelatedColumns(array $relatedColumns) {
		$this->_relatedColumns = $relatedColumns;
	}

	/**
	 * Run the validation
	 * 
	 * @param Model $model
	 * @return boolean
	 */
	public function validate(Model $model) {
		$relatedColumns = $this->_relatedColumns;

		if (!in_array($this->getId(), $relatedColumns)) {
			$relatedColumns[] = $this->getId();
		}

		$query = (new Query());

		foreach ($relatedColumns as $f) {

			//Multiple null values are allowed
			if ($model->{$f} == null) {
				return true;
			}

			$query->andWhere([$f => $model->{$f}]);
		}

		if (!$model->isNew()) {
			$query->andWhere(['!=', $model->pk()]);
		}
		
		$existing = $model->find($query)->single();
		if ($existing) {

			$this->errorCode = 'UNIQUE';
			$this->errorInfo = ['relatedColumns' => $this->_relatedColumns];

			return false;
		}
		return true;
	}
}