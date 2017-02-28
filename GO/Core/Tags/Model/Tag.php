<?php
namespace GO\Core\Tags\Model;

use IFW\Orm\Record;
use IFW\Orm\Query;
/**
 * The contact model
 *
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Tag extends Record{	
	
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * Color hex value without hash sign
	 * @var string
	 */							
	public $color;

	/**
	 * Colors used for tags
	 */
	const COLORS = [
			"F44336", //Red
			"E91E63", //pINK
			"9C27B0", //PURPLE
			"673AB7",
			"3F51B5",
			"2196F3",
			"03A9F4",
			"00BCD4",
			"009688",
			"4CAF50",
			"8BC34A",
			"CDDC39",
			"FFEB3B",
			"FFC107",
			"FF9800",
			"FF5722",
			"795548",
			"9E9E9E",
			"607D8B",
			"000000"
	];
	
	
	protected static function internalGetPermissions() {
		return new TagPermissions();
	}
	
	public function internalValidate() {
		
		$this->name = ucfirst(strtolower($this->name));
		
		return parent::internalValidate();
	}
	
	protected function internalSave() {
		
		if(!isset($this->color)) {
			$count = Tag::find((new Query())->select('count(*)')->fetchMode(\PDO::FETCH_COLUMN, 0))->single();
			
			$modulus = $count % count(self::COLORS);
			
			$this->color = self::COLORS[$modulus];
		}
		
		return parent::internalSave();
	}
	
	/**
	 * Get tags that have links to a particular model
	 * 
	 * @param string $recordClassName The model that links to the model with tags. eg. GO\Modules\Contacts\Model\ContactTag	 * 
	 * @param Query $query
	 * @param bool $countItems Set to true if you want to count the number of items with this tag too. This will be inserted as an extra tag model attribute.
	 * @return Tag[]
	 */
	public static function findForRecordClass($recordClassName, Query $query = null, $countItems = false) {
		
		if(!isset($query)) {
			$query = (new Query());
		}
		
		$query->orderBy(['name' => 'ASC']);		
		
		$relation = $recordClassName::getRelation('tags');
		
//		$query->joinModel($tagLinkModelName, 'id', 'link', 'tagId');
		$viaRecordName = $relation->getViaRecordName();
		$query->join($viaRecordName::tableName(),'link', 't.id=link.tagId');
			
		if($countItems)
		{
			//find other primary key. eg. contactId
			$primaryKeys = $recordClassName::getPrimaryKey();
			foreach($primaryKeys as $colName) {
				if($colName != 'tagId'){
					break;
				}
			}

			$query->select('t.*, count(link.'.$colName.') as count');			
		}
		$query->groupBy(['t.id']);
		
		return self::find($query);
	}
}