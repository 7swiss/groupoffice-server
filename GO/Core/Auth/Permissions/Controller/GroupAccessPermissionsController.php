<?php
namespace GO\Core\Auth\Permissions\Controller;

use GO\Core\Controller;
use IFW\Orm\Query;

abstract class GroupAccessPermissionsController extends Controller {
	
	public static function addRoutesTo(\IFW\Web\Router $router, $baseRoute) {
		$router->addRoutesFor(static::class)
						->get($baseRoute . '/:id/permissions', 'store')
						->put($baseRoute . '/:id/permissions/:groupId', 'set')
						->delete($baseRoute . '/:id/permissions/:groupId', 'delete');
		
	}
	
	abstract function getGroupRecordClassName();
	
	protected function actionStore($id) {		
		
		$cls = $this->getGroupRecordClassName();
	
		$groupAccessRecords = $cls::find(
						(new Query())
						->where([$cls::getForPk() => $id])
						->joinRelation('group', ['id', 'name'])
						);		
		$groupAccessRecords->setReturnProperties('*,group[id,name]');
		
		$this->renderStore($groupAccessRecords);		
	}
	
	protected function actionSet($id, $groupId) {
		$cls = $this->getGroupRecordClassName();	
		
		$groupAccessRecord = $cls::find([$cls::getForPk() => $id, 'groupId' => $groupId])->single();
		
		if(!$groupAccessRecord) {
			$groupAccessRecord = new $cls;
			$groupAccessRecord->{$cls::getForPk()} = $id;
			$groupAccessRecord->groupId = $groupId;			
		}		
		
		$groupAccessRecord->setValues(GO()->getRequest()->body['data']);		
		$groupAccessRecord->save();
		
		$this->renderModel($groupAccessRecord);
	}
	
	protected function actionDelete($id, $groupId) {
		$cls = $this->getGroupRecordClassName();	
		
		$groupAccessRecord = $cls::find([$cls::getForPk() => $id, 'groupId' => $groupId])->single();
	
		$groupAccessRecord->delete();
		
		$this->renderModel($groupAccessRecord);
	}
}
