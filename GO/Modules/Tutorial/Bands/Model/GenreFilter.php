<?php
namespace GO\Modules\Tutorial\Bands\Model;

use IFW\Data\Filter\FilterOption;
use IFW\Data\Filter\MultiselectFilter;
use IFW\Orm\Query;
use PDO;


class GenreFilter extends MultiselectFilter {
	
	/**
	 * Applies the filter on a store query
	 * 
	 * @param Query $query
	 */
	public function apply(Query $query) {
		
		$selected = $this->getSelected();
		
		if(!empty($selected)) {
			$query->joinRelation('albums')
						->groupBy(['id'])
						->andWhere(['albums.genre'=>$selected]);
		}
	}

	/**
	 * Get's all genres
	 * 
	 * Uses a distinct select query on the album genre column
	 * 
	 * @return StringUtil[]
	 */
	public function getOptions() {
		$genres = Album::find(
						(new Query())
						->fetchMode(PDO::FETCH_COLUMN, 0)
						->select('genre')
						->distinct()
						);
		
		$options = [];
		foreach($genres as $genre) {
			$options[] = new FilterOption($this, $genre, $genre, $this->count($genre));
		}
		
		return $options;
		
	}
	
	/**
	 * Counts the number of occurenses but also applies all selected filter 
	 * options in this query.
	 * 
	 * @param StringUtil $genre
	 * @return int
	 */
	private function count($genre){
		
		$query = $this->collection->countQuery();		
		$this->collection->apply($query);		
		$query->where(['albums.genre' => $genre]);
		
		return (int) call_user_func([$this->collection->getModelClassName(), 'find'], $query)->single();
	}

}
