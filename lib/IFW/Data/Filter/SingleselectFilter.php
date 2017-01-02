<?php

namespace IFW\Data\Filter;

use IFW;

/**
 * Abstract filter class
 * 
 * A filter with multiple options but only one of them can be selected
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class SingleselectFilter extends Filter {
	
	/**
	 * The option value that's selected
	 * @var mixed 
	 */
	private $selected;
	
	protected $defaultValue = '';
	
	public $type = self::TYPE_SINGLESELECT;
	
	
	/**
	 * Get's the selected value
	 * 
	 * Note that this must be a string value. Integers must be returned as string.
	 * 
	 * @param string
	 */
	protected function getSelected() {
		if(!isset($this->selected)) {
			if(!empty(IFW::app()->getRequest()->queryParams[$this->getName()])) {			
				$this->selected = IFW::app()->getRequest()->queryParams[$this->getName()];
			}else
			{
				$this->selected = $this->defaultValue;
			}
		}
		
		return $this->selected;
	}
	
	/**
	 * Get the options for this filter
	 * 
	 * @return FilterOption
	 */
	abstract public function getOptions();
}