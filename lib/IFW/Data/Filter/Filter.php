<?php
namespace IFW\Data\Filter;

use IFW\Data\Model;
use IFW\Orm\Query;

/**
 * Abstract filter class
 * 
 * A filter can be used to filter store results.
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class Filter extends Model {
	
	const TYPE_SINGLESELECT = "singleselect";
	
	const TYPE_MULTISELECT = "multiselect";
	
	const TYPE_NUMBERRANGE = "numberrange";
	
	/**
	 * Array of filter class names that should not be used in conjunction and be 
	 * cleared when this filter is used.
	 */
	public function getClearFilters() {
		return [];
	} 
	
	/**
	 *
	 * @var FilterCollection 
	 */
	protected $collection;	
	
	/**
	 * One of the TYPE_ constants of this class
	 * @var string 
	 */
	public $type;
	
//	public static function getDefaultApiProperties() {
//		return ['name', 'label', 'type', 'clearFilters'];
//	}
	
	/**
	 * The name of the filter
	 * 
	 * It defaults to the class name without the namespace.
	 * 
	 * @param string
	 */
	public function getName() {
		$parts = explode('\\', static::class);
		
		return array_pop($parts);
	}
	
	/**
	 * An optional label the client can use
	 * By default the client should present a translated label but in some cases
	 * the label name can come from the database like with custom fields
	 * 
	 * @param string
	 */
	public function getLabel(){
		return null;
	}
	
	/**
	 * When adding a filter to a collection the collection adds itself to the filter.
	 * 
	 * @param \IFW\Data\Filter\FilterCollection $collection
	 */
	public function setFilterCollection(FilterCollection $collection) {
		$this->collection = $collection;		
	}
	
	
//	protected function applyOtherFilters(Query $countQuery) {
//		foreach($this->collection->getFilters() as $filter) {
//			if($filter != $this) {
//				$filter->apply($countQuery);
//			}
//		}
//	}

	/**
	 * Apply this filter to a store query;
	 */
	abstract function apply(Query $query);
	
	
}
