<?php

namespace IFW\Data\Filter;

use IFW\Data\Model;
use IFW\Db\PDO;
use IFW\Orm\Query;

/**
 * A collection of store filters
 * 
 * This collection can hold multiple filters that all add criteria to the store
 * query. They also count the results per filter option.
 * 
 * {@see \GO\Modules\Contacts\Controller\ContactController} for an example.
 * 
 * ````````````````````````````````````````````````````````````````````````````
 *	private function getFilterCollection() {
 *		$filters = new FilterCollection(Contact::class);
 *			
 *		$filters->addFilter(TagFilter::class);
 *		$filters->addFilter(GenderFilter::class);
 *		$filters->addFilter(AgeFilter::class);
 *		
 *		Field::addFilters(ContactCustomFields::class, $filters);
 *		
 *		return $filters;
 *	}
 * ````````````````````````````````````````````````````````````````````````````
 * 
 * Now you can create an action to get the filters:
 * 
 * ````````````````````````````````````````````````````````````````````````````
 * public function actionFilters() {
 *	$this->renderJson($this->getFilterCollection()->toArray());		
 * }
 * ````````````````````````````````````````````````````````````````````````````
 * 
 * In the actionStore function you can apply all filters like this:
 * 
 * ````````````````````````````````````````````````````````````````````````````
 * $this->getFilterCollection()->apply($query);
 * ````````````````````````````````````````````````````````````````````````````
 * 
 * The apply function loops through all filters and applies the value. The
 * client can send the filter value in the query paramters. For example:
 * TagFilter=1,2 will filter on tags with id 1 and 2. The name of the query
 * parameter is equal to the name of the filter. The filter name defaults to the
 * class name but could be overridden in {@see \IFW\Data\Filter::getName()}. 
 * 
 * You can map the route "contacts/filters" to the actionFilters method for example.
 *
 * @deprecated Use filtering on the client side!
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class FilterCollection extends Model {
	/**
	 *
	 * @var Filter 
	 */
	private $filters = [];
	
	private $modelClassName;
	
	private $countQuery;
	
	public function __construct($modelClassName) {
		$this->modelClassName = $modelClassName;
		
		parent::__construct();
	}
	
	
	/**
	 * Set the base count query
	 * 
	 * Useful to set extra conditions. The task executor or permissions for contacts for example.
	 * 
	 * @param Query|array $query
	 */
	public function setCountQuery($query) {
		$this->countQuery = Query::normalize($query);
	}
	
	/**
	 * Get the model class name of the store model
	 * 
	 * eg. "GO\Modules\Contacts\Model\Contact"
	 * 
	 * @param string
	 */
	public function getModelClassName() {
		return $this->modelClassName;
	}
	
	/**
	 * Add a filter to the collection
	 * 
	 * @param string $className
	 * @return Filter
	 */
	public function addFilter($className) {		
		
		$filter = new $className();
		$filter->setFilterCollection($this);
		$this->filters[] = $filter;
		
		return $filter;
	}
	
	/**
	 * Get's the filter instance by class name
	 * 
	 * @param string $className
	 * @return Filter
	 */
	public function getFilter($className) {
		foreach($this->filters as $filter) {
			if($filter->getClassName() == $className) {
				return $filter;
			}
		}
		return false;
	}
	
	/**
	 * Applies all filters to the store query
	 * 
	 * It loops trough all filters in the collection and calls apply on each filter
	 * The implementation of apply varies per filter.
	 * 
	 * Example filters for contacts could be Age, Gender and Tags.
	 * 
	 * @param Query $query
	 */
	public function apply(Query $query, Filter $skipThisFilter = null) {		
		
		if(empty(GO()->getRequest()->queryParams['useFilters'])) {
			return;
		}
		
		foreach($this->filters as $filter) {
			if(!isset($skipThisFilter) || ($filter != $skipThisFilter && !in_array($filter->getClassName(), $skipThisFilter->clearFilters))) {			
				$filter->apply($query);
			}
		}		
	}
	
	/**
	 * Get's a base query object for counting results.
	 * 
	 * Example:
	 * {@see \GO\Core\Modules\Tags\Filter\TagFilter::_count()}
	 * 
	 * @return Query
	 */
	public function countQuery() {
		$query = isset($this->countQuery) ? clone $this->countQuery : (new Query());		
		
		$query->select('COUNT(DISTINCT t.id)')
						->fetchMode(PDO::FETCH_COLUMN, 0);
		
		return $query;
	}
	/**
	 * 
	 * @return Filter
	 */
	public function getFilters() {
		return $this->filters;
	}

//	/**
//	 * Counts the total of unfiltered results.
//	 * 
//	 * @return int
//	 */
//	public function getCount() {		
//		$query = $this->countQuery();
//		$this->apply($query);
//		
//		return (int) call_user_func([$this->modelClassName, 'find'], $query)->single();
//	}	
}