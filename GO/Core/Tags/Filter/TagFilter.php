<?php
namespace GO\Core\Tags\Filter;

use IFW\Data\Filter\MultiselectFilter;
use GO\Core\Tags\Filter\TagFilterOption;
use IFW\Db\Criteria;
use IFW\Orm\Query;
use IFW\Orm\Relation;
use GO\Core\Tags\Model\Tag;

class TagFilter extends MultiselectFilter {
	
//	public function getOptions() {
//		$query = $this->collection->countQuery();
////		$this->collection->apply($query);
//		
//		$this->applyOtherFilters($query);
//		
//		$query->select('count(t.id) AS count, tags.id, tags.name')
//						->joinRelation('tags')						
//						->setFetchMode(PDO::FETCH_ASSOC)
//						->orderBy(['tags.name'=>'ASC'])
//						->groupBy(['tags.id']);
//		
//		$this->applyOtherFilters($query);
////		$this->collection->apply($query);
//		
//		$records = call_user_func([$this->collection->getModelClassName(), 'find'], $query);
//		
//		$results = [];
//		foreach($records as $record) {			
//			$tagOption = new FilterOption($this, $record['id'], $record['name'], $record['count']);			
//			$results[] = $tagOption;
//		}
//		return $results;
//	}
	
	public function getOptions() {
		
		$tags = Tag::findForRecordClass($this->collection->getModelClassName(), null, false);
		
		$results = [];
		foreach($tags as $tag) {
			$count = $this->count($tag->id);
//			if($count) {
				$tagOption = new TagFilterOption($this, $tag->id, $tag->name, $count);
				$tagOption->setColor($tag->color);
				$results[] = $tagOption;
//			}
		}
		
		return $results;
	}
	
	private function count($tagId) {
		
		return null;
					
//		$query = $this->collection->countQuery();
//		$this->collection->apply($query);
////		$this->applyOtherFilters($query);		
//		
//		$tagRelation = $this->getTagRelation();
//		
////		$fk = $tagRelation->getForeignKey();
//		$on = '';
//		foreach($tagRelation->getKeys() as $fromField => $toField) {				
//			if(!empty($on)) {
//				$on .= ' AND ';
//			}
//			$on .= 't.'.$fromField.'=tagLink2.'.$toField;
//		}
//		
//		$query->join(
//						$tagRelation->getViaRecordName(), 
//						'tagLink2',
//						(new Criteria())
//							->where($on)
//							->andWhere(['tagLink2.tagId' => $tagId])
//						);
//
//		//clear group by for count distinct
//		$query->groupBy([]);
//		
//		return (int) call_user_func([$this->collection->getModelClassName(),'find'], $query)->single();
	}
	
	/**
	 * 
	 * @return Relation
	 */
	private function getTagRelation() {
		return call_user_func([$this->collection->getModelClassName(),'getRelation'], 'tags');
	}
	
	public function apply(Query $query) {		
		
		if(!empty($this->selected)) {
			$tagRelation = $this->getTagRelation();	
					
			$on = '';
			foreach($tagRelation->getKeys() as $fromField => $toField) {				
				if(!empty($on)) {
					$on .= ' AND ';
				}
				$on .= 't.'.$fromField.'=tags.'.$toField;
			}
			
			$subquery = (new Query())
							->tableAlias('tags')
							->where($on)
							->andWhere(['tags.tagId'=>$this->selected]);

			$tagLinkModel = $tagRelation->getViaRecordName();
			
			$query->where(['EXISTS', $tagLinkModel::find($subquery)]);
//			$query->groupBy(['t.id']);
		}
	}
}