<?php

namespace GO\Core\Modules\Controller;

use IFW;
use GO\Core\Users\Model\Group;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Db\Criteria;
use IFW\Orm\Query;

class PermissionsController extends Controller {
	
	
	/**
	 * Get groups with the permission types as concattenated string (1,2,3)
	 * 
	 * @param string $modelName eg. GO\Modules\Model\Contact
	 * @param int $moduleId
	 * @return self[]
	 */
	private function findGroups($moduleId) {

		$query = (new Query())
						->select('t.id, t.name, GROUP_CONCAT(moduleGroups.action) AS actions')
						->orderBy(['name' => 'ASC'])
						->groupBy(['t.id','t.name'])
//						->where(['userId' => null])
//						->orWhere(['!=',['permissions.aclId' => null]])		
						->fetchMode(\PDO::FETCH_ASSOC)
						->joinRelation(
						'moduleGroups', false, 'INNER', (new Criteria())->where(['moduleGroups.moduleId' => $moduleId])
		);

		$groups = Group::find($query);
		
//		echo $groups;
		
		return $groups;
	}

	/**
	 * @param type $moduleName
	 * @param type $searchQuery
	 * @return type
	 */
	public function store($moduleName, $searchQuery = "") {

		$module = \GO\Core\Modules\Model\Module::find(['name' => $moduleName])->single();
		
		$permissionTypes = $module->getPermissionTypes();	
//		array_filter($permissionTypes, function($permissionType) {
//			return $permissionType['name'] != \IFW\Auth\Permissions\Model::PERMISSION_READ && $permissionType['readonly'] = false;
//		});
		
		$groups = $this->findGroups($module->id);
		$store = new Store($groups);
		$store->format('permissions', function($group) use ($permissionTypes, $module) {
			$ts = isset($group['actions']) ? explode(',', $group['actions']) : [];

			$v = [];
			foreach ($permissionTypes as $permissionType) {
				
				if(!$permissionType['readonly']) {					
					$v[$permissionType['name']] = in_array($permissionType['name'], $ts);
				}
			}

			return $v;
		});
		$return = $this->renderStore($store);

		$return->data['actions'] = $permissionTypes;

		return $return;
	}

	public function create($moduleName, $groupId, $action) {	
		$module = \GO\Core\Modules\Model\Module::find(['name' => $moduleName])->single();
		
		$pk = ['moduleId' => $module->id, 'groupId' => $groupId, 'action' => $action];
		$model = \GO\Core\Modules\Model\ModuleGroup::findByPk($pk);
		
		$success = true;
		if(!$model) {
			$model = new \GO\Core\Modules\Model\ModuleGroup();
			$model->setValues($pk);
			$success = $model->save();
		}
	
		$this->render(['success'=>$success]);
	}
	
	public function delete($moduleName, $groupId, $action) {	
		
		$module = \GO\Core\Modules\Model\Module::find(['name' => $moduleName])->single();
	
		$pk = ['moduleId' => $module->id, 'groupId' => $groupId, 'action' => $action];
		$model = \GO\Core\Modules\Model\ModuleGroup::findByPk($pk);
		
		if(!$model) {
			throw new \IFW\Exception\NotFound();
		}
	
		$this->render(['success'=>$model->delete()]);
	}
	
	public function deleteGroup($moduleName, $groupId) {	

		$module = \GO\Core\Modules\Model\Module::find(['name' => $moduleName])->single();
		
		$models = \GO\Core\Modules\Model\ModuleGroup::find(['moduleId' => $module->id, 'groupId' => $groupId]);
		
		foreach($models as $model) {
			if(!$model->delete()){
				$this->render(['success'=>false]);
				exit();
			}
		}
	
		$this->render(['success'=>true]);
	}
}