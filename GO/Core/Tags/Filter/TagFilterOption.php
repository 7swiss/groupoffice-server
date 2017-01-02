<?php
namespace GO\Core\Tags\Filter;

use IFW\Data\Filter\FilterOption;

class TagFilterOption extends FilterOption {
	
	public $color = '';
	
	public function setColor($color){
		$this->color = $color;
	}
	
}