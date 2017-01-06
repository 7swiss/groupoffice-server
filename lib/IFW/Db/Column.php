<?php

namespace IFW\Db;

use IFW\Util\DateTime;
use Exception;

/**
 * Represents a Record database column attribute.
 * 
 * <p>Example:</p>
 * <code>
 * $model = User::findByPk(1);
 * echo $model->getColumn('username')->length;
 * </code>
 * 
 * If you want to override a column parameter then override Record::getColumns():
 * 
 * `````````````````````````````````````````````````````````````````````````````
 * public static function getColumns() {
 * 		$columns = parent::getColumns();		
 * 		$columns['password']->trimInput = false;
 * 		
 * 		return $columns;		
 * 	}
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Column {

	/**
	 * false if non unique or an array of columns that should be unique in combination with this column.
	 * 
	 * @var bool|array 
	 */
	public $unique = false;

	/**
	 * Is this part of the primary key
	 * 
	 * @var bool  
	 */
	public $primary = false;

	/**
	 * Name of the column
	 * 
	 * @var string 
	 */
	public $name;

	/**
	 * Length of the column
	 * 
	 * @var int
	 */
	public $length;

	/**
	 * True if null is allowed
	 * 
	 * @var boolean 
	 */
	public $nullAllowed;

	/**
	 * True if this column auto increments
	 * 
	 * @var boolean
	 */
	public $autoIncrement = false;

	/**
	 * Field type in the database
	 * 
	 * @var string 
	 */
	public $dbType;

	/**
	 * PDO Type
	 * 
	 * @var int 
	 */
	public $pdoType;

	/**
	 * True if field is required
	 * 
	 * @var boolean 
	 */
	public $required;

	/**
	 * Default value of the column
	 * 
	 * @var mixed 
	 */
	public $default;

	/**
	 * The column comment
	 * 
	 * @var string 
	 */
	public $comment;

	/**
	 * Trim white spaces on input
	 * 
	 * @var boolean 
	 */
	public $trimInput = false;

	/**
	 * The MySQL database datetime format.
	 */
	const DATETIME_FORMAT = "Y-m-d H:i:s";

	/**
	 * The MySQL database date format.
	 */
	const DATE_FORMAT = "Y-m-d";

	/**
	 * Default value of dates are stored as times. We must refresh them
	 */
	public function __wakeup() {
		if (isset($this->default)) {
			if ($this->dbType == 'date') {
				$this->default = date(Column::DATE_FORMAT);
			} else if ($this->dbType == 'datetime') {
				$this->default = date(Column::DATETIME_FORMAT);
			}
		}
	}

	/**
	 * Input formatting for the database.
	 * Currently only used for date fields because we want ISO 8601 for I/O.
	 * 
	 * @param mixed $value
	 * @return mixed
	 */
	public function normalizeInput($value) {
		if (!isset($value)) {
			return null;
		}
		
		switch ($this->dbType) {
			case 'datetime':
				if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
					return $value;
				} else {
					$dt = new DateTime($value);
					$dt->setTimezone(new \DateTimeZone(date_default_timezone_get())); //UTC
					return $dt;
				}

			case 'date':
				//make sure date is formatted correctly
				if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
					return $value;
				} else {
					return new DateTime($value);
				}
				
			default:
				if ($this->trimInput) {
					$value = trim($value);
				}

				return $value;				
		}
	}
	
	public function recordToDb($value) {
		if (!isset($value)) {
			return null;
		}
		
		switch ($this->dbType) {
			case 'datetime':				
				return $value->format(self::DATETIME_FORMAT);

			case 'date':
				return $value->format(self::DATE_FORMAT);
				
			default:
				return $value;
		}
	}

	/**
	 * Output formatting for the database.
	 * Currently only used for date fields because we want ISO 8601 for I/O.
	 * 
	 * @param mixed $value
	 * @return mixed
	 */
	public function dbToRecord($value) {

		if (!isset($value)) {
			return null;
		}
		switch ($this->dbType) {
			case 'int':
			case 'tinyint':
			case 'bigint':
				if ($this->length === 1) {
					//Boolean fields in mysql are listed at tinyint(1);
					return (bool) $value;
				} else {
					// Use floatval because of ints greater then 32 bit? Problem with floatval that ints will set as modified attribute when saving.
					return (int) $value;
				}

			case 'float':
			case 'double':
			case 'decimal':
			case 'real':
				return doubleval($value);

			case 'date':
			case 'datetime':
				return $value instanceof DateTime ? $value: new DateTime($value);
				
			default:
				return $value;
		}
	}

}
