<?php
namespace IFW\Csv;

abstract class File extends \IFW\Data\Model {
	
	/**
	 *
	 * @var \IFW\Fs\File
	 */
	private $file;
	
	/**
	 * The char that delimits fields
	 * @var string 
	 */
	public $delimiter = ",";
	
	/**
	 * The char the encloses fields.
	 * 
	 * @var string 
	 */
	public $enclosure = '"';
	
	private $fp;
	
	/**
	 * Construct CSV file
	 * 
	 * @param \IFW\Fs\File $file
	 */
	public function __construct(\IFW\Fs\File $file) {
		$this->file = $file;
		
		parent::__construct();
	}
	
	
	/**
	 * By default all values are trimmed, checked for valid UTF8 and set to NULL if
	 * empty.
	 * 
	 * @param string $value
	 * @return string
	 */
	protected function sanitize($value) {
		$value = \IFW\Util\StringUtil::cleanUtf8(trim($value));
		
		if(empty($value)) {
			return null;
		}
		
		return $value;		
	}
	
	/**
	 * Get the next record of the CSV file
	 * 
	 * @return array Depends on the mapping {@see map()}
	 */
	public function nextRecord() {
		
		if(!isset($this->fp)) {
			$this->fp = $this->file->open('r');
		}
		
	 $record = fgetcsv($this->fp, 0, $this->delimiter, $this->enclosure);	 
	 
	 if(!$record) {
		 return false;
	 }
	 
	 return array_map([$this, "sanitize"], $record);	 
	}	
}