<?php
namespace IFW\Data\Filter;

use IFW;

/**
 * Number range filter
 *
 * For filtering a number between a min and max value. This class just sets a 
 * min and max property from getparam=min,max. You have to implement the
 * filtering yourself in the apply() function.
 * 
 * @property int $min Number should be equal or greater than this value 
 * @property int $max Number should be equal or lower than this value 
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class NumberrangeFilter extends Filter {
	
	/**
	 *
	 * @var int 
	 */
	private $min;
	
	/**
	 *
	 * @var int
	 */
	private $max;
	
	public $type = self::TYPE_NUMBERRANGE;
	
	

	public function getMin() {
		$this->setMinMax();
		return $this->min;
	}
	
	public function getMax() {
		$this->setMinMax();
		return $this->max;
	}
	
	private function setMinMax() {		
		
		if(!isset($this->min)) {		
			if(!empty(IFW::app()->getRequest()->queryParams[$this->getName()])) {			
				$selected = explode(',', IFW::app()->getRequest()->queryParams[$this->getName()]);

				$this->min = intval($selected[0]);
				$this->max = intval($selected[1]);
			}else
			{
				$this->min = $this->max = 0;
			}
		}
	}
}