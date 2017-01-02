<?php
//namespace GO\Core\Tags\Filter;
//
//use IFW\Data\Filter\AbstractFilter;
//use IFW\Orm\Query;
//
//class UntaggedFilter extends AbstractFilter {
//	
//	public $name = 'Untagged';
//		
//	public function applyQuery(Query $query) {		
//		$tagRelation = call_user_func([$this->modelClassName,'getRelation'], 'tags');		
//		$query->joinModel($tagRelation->getVia(), $tagRelation->getKey() , 'tagLink', $tagRelation->getForeignKey(), 't', 'LEFT');			
//		$query->andWhere(['tagLink.tagId' => null]);
//	}
//
//	public function getCount() {
//		
//		$tagRelation = call_user_func([$this->modelClassName,'getRelation'], 'tags');		
//		
//		$tagLinkModelName = $tagRelation->getVia();
//		
//		$query = (new Query())
//						->setFetchMode(\PDO::FETCH_COLUMN, 0)
//						->select("count(id)")
//						->where("NOT EXISTS (SELECT tagId FROM  ".$tagLinkModelName::tableName()." tagLink WHERE tagLink.".$tagRelation->getForeignKey()."=t.".$tagRelation->getKey().")");
//		
//		
//		return call_user_func([$this->modelClassName, "find"],$query)->single();
//	}
//}