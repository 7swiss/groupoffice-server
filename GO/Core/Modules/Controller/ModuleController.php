<?php

namespace GO\Core\Modules\Controller;

use GO\Core\Controller;
use GO\Core\Modules\Model\InstallableModule;
use GO\Core\Modules\Model\Module;
use IFW;
use IFW\Data\Filter\FilterCollection;
use IFW\Data\Store;
use IFW\Exception\Forbidden;
use IFW\Exception\NotFound;
use IFW\Modules\ModuleCollection;
use IFW\Orm\Query;

/**
 * The controller for groups. Admin group is required.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ModuleController extends Controller {

	public function filters() {
		$this->render($this->getFilterCollection()->toArray());		
	}
	
	private function getFilterCollection() {
		$filters = new FilterCollection(Module::class);
		$filters->addFilter(\GO\Core\Modules\Model\InstalledFilter::class);
		$filters->addFilter(\GO\Core\Modules\Model\AuthorFilter::class);
		
		return $filters;
	}
	
	/**
	 * Fetch modules
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function store($orderColumn = 'id', $orderDirection = 'ASC',$searchQuery = "") {

		$query = (new Query())					
						->orderBy([$orderColumn => $orderDirection])
						->search($searchQuery, ['t.name']);

		$modules = Module::find($query)->all();
		
		$modules = array_filter($modules, function($module) {
			return class_exists($module->name);
		});
		
		$this->renderStore($modules);
	}

	public function allModules($returnProperties='*', $searchQuery = null) {
		
		$records = [];
		
		$filters = $this->getFilterCollection();
		$installedFilter = $filters->getFilter(\GO\Core\Modules\Model\InstalledFilter::class);
		
		$authorFilter = $filters->getFilter(\GO\Core\Modules\Model\AuthorFilter::class);
		
		$modules = new ModuleCollection(false);
		
		foreach ($modules as $className) {			
			$instance = new $className;
			if($instance instanceof InstallableModule) {
				
				$module = Module::find((new Query())->where(['name' => $className])->withDeleted())->single();
				if (!$module) {
					$module = new Module();
					$module->name = $className;
				}
				
				if($installedFilter->filter($module) && $authorFilter->filter($module)) {
					$records[] = $module;
				}
			}
		}
		
		$records = array_filter($records, function($record) use ($searchQuery) {
			if(!empty($searchQuery) && !preg_match('/'.preg_quote($searchQuery,'/').'.*/i', $record->moduleInformation['languages']['en']['name'])) {
				return false;
			}
			
			
			
			return true;
			
		});


		$store = new Store($records);
		$store->setReturnProperties($returnProperties);
		$this->renderStore($store);
	}
	
	/**
	 * Create a new module. Use GET to fetch the default attributes or POST to add a new module.
	 *
	 * The attributes of this module should be posted as JSON in a module object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"className":"\GO\Modules\Bands\BandsModule"}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function newInstance($returnProperties = "") {

		//Check edit permission
		$module = new Module();
		if (!GO()->getAuth()->user() || !GO()->getAuth()->isAdmin()) {
			throw new Forbidden();
		}

		$this->renderModel($module, $returnProperties);
	}

	/**
	 * Create a new module. Use GET to fetch the default attributes or POST to add a new module.
	 *
	 * The attributes of this module should be posted as JSON in a module object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"className":"\GO\Modules\Bands\BandsModule"}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($returnProperties = "") {

		//Check edit permission
		$module = new Module();
		if (!GO()->getAuth()->user() || !GO()->getAuth()->isAdmin()) {
			throw new Forbidden();
		}

		$module->setValues(GO()->getRequest()->body['data']);
		$module->save();

		$this->renderModel($module, $returnProperties);
	}
	
	/**
	 * Use module name here so we can read modules that are not installed and not in te database.
	 * 
	 * @param type $moduleName
	 * @param type $returnProperties
	 * @throws NotFound
	 */
	public function read($moduleName, $returnProperties = "") {

		$module = Module::find(['name' => $moduleName])->single();

		if (!$module) {
			
			$module = new Module();
			$module->name = $moduleName;
						
//			throw new NotFound();
		}

		$this->renderModel($module, $returnProperties);
	}

	/**
	 * Update a field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"fieldname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $moduleName The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($moduleName, $returnProperties = "") {

		$module = Module::find((new IFW\Orm\Query)->where(['name' => $moduleName])->withDeleted())->single();

		if (!$module) {
			$module = new Module();
			$module->name = $moduleName;
		}

		if($module->deleted) {
			$module->deleted = false;
		}
		$module->setValues(GO()->getRequest()->body['data']);		
		$module->save();
		
		$this->renderModel($module, $returnProperties);
	}

	/**
	 * Delete a field
	 *
	 * @param int $moduleName
	 * @throws NotFound
	 */
	public function delete($moduleName) {
		$module = Module::find(['name' => $moduleName])->single();

		if (!$module) {
			throw new NotFound();
		}

		$module->delete();

		$this->renderModel($module);
	}

	
	/**
	 * Update multiple records at once with a PUT request.
	 * 
	 * @example multi delete
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 *	"data" : [{"id" : 1, "markDeleted" : true}, {"id" : 2, "markDeleted" : true}]
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * @throws NotFound
	 */
	public function multiple() {
		
		$response = ['data' => []];
		
		foreach(GO()->getRequest()->getBody()['data'] as $values) {
			
			if(!empty($values['id'])) {
				$record = Module::findByPk($values['id']);

				if (!$record) {
					throw new NotFound();
				}
			}else
			{
				$record = new Module();
			}
			
			$record->setValues($values);
			$record->save();
			
			$response['data'][] = $record->toArray();
		}
		
		$this->render($response);
	}
}
