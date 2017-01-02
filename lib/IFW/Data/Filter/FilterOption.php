<?php
namespace IFW\Data\Filter;

use IFW\Data\Model;

class FilterOption extends Model {
	
	/**
	 * Value of the option
	 * @var mixed 
	 */
	public $value;
	
	/**
	 * Text label 
	 * 
	 * @var string 
	 */
	public $label;
	
	/**
	 * The number of results with this option
	 * 
	 * It also takes the other enabled filter options into account.
	 * 
	 * @var int 
	 */
	public $count;
	
	
	/**
	 *
	 * @var Filter 
	 */
	private $filter;
		
	/**
	 * Constructor
	 * 
	 * @param \IFW\Data\Filter\Filter $filter
	 * @param mixed $value
	 * @param string $label
	 * @param int $count
	 */
	public function __construct(Filter $filter, $value, $label, $count = null) {
		
		$this->count = $count;
		$this->label = $label;
		$this->value = (string) $value;
		$this->filter = $filter;
		
		parent::__construct();
	}	
	
	/**
	 * True if this option is selected
	 * 
	 * @return boolean
	 */
	public function getSelected() {
		
		$selected = $this->filter->selected;
		
		return is_array($selected) ? in_array($this->value, $selected) : $this->value === $selected;
	}
}