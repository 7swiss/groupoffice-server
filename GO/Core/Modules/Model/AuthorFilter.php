<?php
namespace GO\Core\Modules\Model;

use IFW\Data\Filter\FilterOption;
use IFW\Data\Filter\MultiselectFilter;
use IFW\Orm\Query;
use IFW\Util\ClassFinder;


class AuthorFilter extends MultiselectFilter {
	
	
		
	public function getOptions() {		
		
		
		$cf = new ClassFinder();
		$installableModules = $cf->findByParent(InstallableModule::class);	
		
		$options = [];
		$authors = [];
		foreach($installableModules as $module) {
			
			$instance = new $module;
			
			$authors[] = $instance->getModuleInformation()['author'];
			
		}		
		$authors = array_unique($authors);
		
		foreach($authors as $author) {
			$options[]= new FilterOption($this, $author, $author);
		}
		return $options;
	}
	

	
	public function apply(Query $query) {		
		
//		if(!empty($this->selected)) {			
//			$query->andWhere(['event.recordTypeId' => $this->selected]);
//		}
	}
	
	public function filter(\GO\Core\Modules\Model\Module $record) {
		if(empty($this->getSelected())) {
			return true;
		}
		
		return in_array($record->getModuleInformation()['author'], $this->getSelected());
	}
}
