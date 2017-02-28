<?php
namespace GO\Core\Users\Model;

use IFW\Data\Filter\MultiselectFilter;
use IFW\Data\Filter\FilterOption;
use IFW\Db\Criteria;
use IFW\Orm\Query;
use IFW\Orm\Relation;
use GO\Core\Users\Model\Group;

class GroupFilter extends MultiselectFilter {
	
	
	public function getOptions() {
		$groups = Group::find(['userId' => null]);
		
		$results = [];
		foreach($groups as $group) {			
			$groupOption = new FilterOption($this, $group->id, $group->name, $this->_count($group->id));			
			$results[] = $groupOption;
		}
		
		return $results;
	}
	
	private function _count($groupId) {
					
		$query = $this->collection->countQuery();
		$this->collection->apply($query);
//		$this->applyOtherFilters($query);		
		
		$groupRelation = $this->_getGroupRelation();
		$viaRecordName = $groupRelation->getViaRecordName();
		$query->join(
						$viaRecordName::tableName(), 
						'groupLink2',
						(new Criteria())
							->where('t.id=groupLink2.userId')
							->andWhere(['groupLink2.groupId' => $groupId])
						);
		
//		$query->joinModel($groupRelation->getVia(), $groupRelation->getKey() , 'groupLink2', $groupRelation->getForeignKey());
//		$query->andWhere(['groupLink2.groupId' => $groupId]);
		
		//clear group by for count distinct
		$query->groupBy([]);
		

		
		return (int) call_user_func([$this->collection->getModelClassName(),'find'], $query)->single();
	}
	
	/**
	 * 
	 * @return Relation
	 */
	private function _getGroupRelation() {
		return call_user_func([$this->collection->getModelClassName(),'getRelation'], 'groups');
	}	
	
	public function apply(Query $query) {		
		
		if(!empty($this->selected)) {
			$groupRelation = $this->_getGroupRelation();		
//			$query->joinModel($groupRelation->getVia(), $groupRelation->getKey() , 'groupLink', $groupRelation->getForeignKey());
//			$query->groupBy(['t.id'])->andWhere(['IN','groupLink.groupId',$this->selected]);
			$viaRecordName = $groupRelation->getViaRecordName();
			$query->join(
							$viaRecordName::tableName(), 
							'groupLink',
							(new Criteria())
								->where('t.id=groupLink.userId')
								->andWhere(['groupLink.groupId' => $this->selected])
							);
			$query->groupBy(['t.id']);
		}
	}
}