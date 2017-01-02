<?php

namespace GO\Modules\GroupOffice\Messages\Model;

use IFW\Data\Filter\SingleselectFilter;
use IFW\Data\Filter\FilterOption;
use IFW\Orm\Query;


class TypeFilter extends SingleselectFilter {
	
	protected $defaultValue = 'incoming';
	
	public function getOptions() {

		$results = [
				new FilterOption($this, "incoming", "Incoming", null),
				new FilterOption($this, "unread", "Unread", $this->_count("unread")),
				new FilterOption($this, "flagged", "Flagged", $this->_count("flagged")),
				new FilterOption($this, "actioned", "Actioned", null),
				new FilterOption($this, "sent", "Sent", null),
				new FilterOption($this, "drafts", "Drafts", null),
				new FilterOption($this, "trash", "Trash", null),
				new FilterOption($this, "junk", "Junk", null),
				new FilterOption($this, "outbox", "Outbox", $this->_count("outbox"))
				];

		return $results;
	}
	
	public function apply(Query $query) {
		
		$subquery = (new Query())
								->select('id')
								->tableAlias('m')
								->where('m.threadId=t.id');
		
		switch($this->getSelected()) {
			case 'incoming':
				$subquery->andWhere(['type'=>  Message::TYPE_INCOMING]);
				break;
			
			case 'unread':
				$subquery->andWhere(['type'=>  Message::TYPE_INCOMING, 'seen'=>false]);		
				break;
			case 'flagged':
				$subquery->andWhere(['flagged'=>true]);
				break;
			
			case 'actioned':
				$subquery->andWhere(['type'=>  Message::TYPE_ACTIONED]);				
				break;
			case 'sent':
				$subquery->andWhere(['type'=>  Message::TYPE_SENT]);				
				break;
			
			case 'drafts':
				$subquery->andWhere(['type'=>  Message::TYPE_DRAFT]);
				break;
			case 'trash':
				$subquery->andWhere(['type'=>  Message::TYPE_TRASH]);				
				break;
			case 'junk':
				$subquery->andWhere(['type'=>  Message::TYPE_JUNK]);				
				break;
			
			case 'outbox':
				$subquery->andWhere(['type'=>  Message::TYPE_OUTBOX]);				
				break;
		}
		
		$query->andWhere(['EXISTS', Message::find($subquery)]);
		
	}

	private function _count($type) {
		
		$query = $this->collection->countQuery();		
		$this->collection->apply($query, $this);
		
		switch($type) {
			
			case 'unread':
//				$subquery = Message::find((new Query())
//								->select('id')
//								->tableAlias('m')
//								->where('m.threadId=t.id')
//								->andWhere(['type'=>  Message::TYPE_INCOMING])
//								);
				
				$query->joinRelation('messages');
				$query->andWhere(['seen'=>false])
								->andWhere(['messages.type'=>Message::TYPE_INCOMING]);
				
				break;
			
			case 'flagged':
				$query->andWhere(['flagged'=>true]);
				break;		
			
			case 'outbox':
				$query->joinRelation('messages');
				$query->andWhere(['messages.type'=>Message::TYPE_OUTBOX]);
				break;
			
		}
		
		$count = Thread::find($query)->single();
		
		if(!$count) {
			return null;
		}else
		{
			return $count;
		}
	}

}

