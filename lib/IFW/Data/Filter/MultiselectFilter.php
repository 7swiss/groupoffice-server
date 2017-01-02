<?php
namespace IFW\Data\Filter;

use IFW;

/**
 * Abstract filter class for multi select options
 * 
 * Example: {@see \GO\Core\Modules\Tags\Filter\TagFilter} 
 * 
 * @property array $selected
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class MultiselectFilter extends Filter {
	
	private $selected;
	
	public $type = self::TYPE_MULTISELECT;
	
//	public static function getDefaultApiProperties() {
//		return array_merge(parent::getDefaultApiProperties(), ['selected', 'options']);
//	}
	

	/**
	 * Get's the selected value
	 * 
	 * Note that this must be a string value. Integers must be returned as string.
	 * 
	 * @param string[]
	 */
	protected function getSelected() {
		if(!isset($this->selected)) {
			if(isset(IFW::app()->getRequest()->queryParams[$this->getName()]) && IFW::app()->getRequest()->queryParams[$this->getName()] !== '') {			
				$this->selected = explode(',', IFW::app()->getRequest()->queryParams[$this->getName()]);
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