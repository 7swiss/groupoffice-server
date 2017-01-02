<?php
namespace GO\Modules\GroupOffice\Contacts\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Contacts\Model\ContactGroup;
use IFW\Orm\Query;

class PermissionsController extends Controller {
	protected function actionStore($contactId) {		
		$contactGroups = ContactGroup::find(
						(new Query())
						->where(['contactId'=>$contactId])
						->andWhere(['!=',['groupId'=>  \GO\Core\Users\Model\Group::ID_ADMINS]])
						->joinRelation('group', ['id', 'name'])
						);		
		$contactGroups->setReturnProperties('*,group[id,name]');
		
		$this->renderStore($contactGroups);		
	}
	
	protected function actionSet($contactId, $groupId) {
		$contactGroup = ContactGroup::find(['contactId'=>$contactId, 'groupId'=>$groupId])->single();
		
		if(!$contactGroup) {
			$contactGroup = new ContactGroup();
			$contactGroup->contactId = $contactId;
			$contactGroup->groupId = $groupId;			
		}		
		
		$contactGroup->setValues(GO()->getRequest()->body['data']);		
		$contactGroup->save();
		
		$this->renderModel($contactGroup);
	}
	
	protected function actionDelete($contactId, $groupId) {
		$contactGroup = ContactGroup::find(['contactId'=>$contactId, 'groupId'=>$groupId])->single();
		
	
		$contactGroup->delete();
		
		$this->renderModel($contactGroup);
	}
}
