<?php
namespace IFW\Data\Filter;

/**
 * Abstract boolean filter class
 * 
 * Filters boolean model attributes
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class BooleanFilter extends MultiselectFilter {
	
	public function getOptions() {
		return [
				new FilterOption($this, "0", "Unchecked", $this->count(false)),
				new FilterOption($this, "1", "Checked", $this->count(true))
		];
	}
	
	abstract function count($checked);
}