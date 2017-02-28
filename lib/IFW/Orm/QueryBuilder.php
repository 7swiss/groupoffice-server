<?php
namespace IFW\Orm;

use Exception;
use IFW\Db\Criteria;
use IFW\Db\QueryBuilder as DbQueryBuilder;
use IFW\Orm\Relation;

/**
 * QueryBuilder
 * 
 * Builds or executes an SQL string with a {@see Query} object and {@see Record}
 * 
 * @todo join relation is not fully implemented here. It's half in db package.
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class QueryBuilder extends DbQueryBuilder{	

	/**
	 * Join relation can automatically add select from columns. These must be appended afterwards
	 * @var string 
	 */
	protected $joinRelationSelectString;
	
	private $recordClassName;
	
	public function __construct($recordClassName) {
		parent::__construct($recordClassName::tableName());
		$this->recordClassName = $recordClassName;
	}
	
	/**
	 * Get the name of the record this query builder is for.
	 *
	 * @param string
	 */
	public function getRecordClassName() {
		return $this->recordClassName;
	}
	
	protected function joinRelation($joinRelation) {

		$join = '';
		$names = explode('.', $joinRelation['name']);
		$relationAlias = $this->tableAlias;
		$attributePrefix = '';

		$relationModel = $this->recordClassName;

		foreach ($names as $name) {

			/* @var $r Relation  */

			$r = $relationModel::getRelation($name);

			if (!$r) {
				throw new Exception("Can't join non existing relation '$name' of model '$relationModel'.");
			}

			$attributePrefix .= $name . '@';
			
			$selectAttributes = $name == end($names) ? $joinRelation['selectAttributes'] : false;
			
			
			//only add criteria to last element
			$on = $name == end($names) ? $joinRelation['on'] : null;

			$joinParams = $this->buildJoinRelation($r, $relationAlias, $joinRelation['type'], $attributePrefix, $selectAttributes, $on);
			

			if (!in_array($name, $this->alreadyJoinedRelations)) {
				$join .= $joinParams['joinSql'];
			}
			$this->alreadyJoinedRelations[] = $name;

			if (!empty($selectAttributes)) {
				$this->joinRelationSelectString .= ",\n" . $joinParams['selectCols'];
			}

			$relationModel = $r->getToRecordName();
			$relationAlias = $name;
		}

		return $join;
	}


	/**
	 * Creates joinSql and a select string of attributes. Used by ActiveRecord
	 * when joinRelation() is used in FindParams
	 *
	 * @TODO: HIER STAAT EEN HOOP DUBBELE CODE, DEZE GRAAG MINIMALIZEREN TOT 1 SUB FUNCTIE EN IN OVERLEG MET HET TEAM
	 * 
	 * @param Relation $relationTableAlias
	 * @param string $primaryTableAlias
	 * @param string $joinType
	 * @param string $attributePrefix
	 * @param boolean|array $selectAttributes
	 * @return array
	 */
	protected function buildJoinRelation(Relation $relation, $primaryTableAlias, $joinType, $attributePrefix, $selectAttributes = false, Criteria $on = null) {

		
		$relatedModelName = $relation->getToRecordName();
		
		$joinSql = '';
		if(($viaRecordName = $relation->getViaRecordName())) {			
			$linkTableAlias = $relation->getName().'Link';
			
			
			$joinSql .= $joinType . ' JOIN `'.$viaRecordName::tableName().'` `'.$linkTableAlias.'` ON (';
			
			//ContactTag.tagId -> tag.id
			
			foreach($relation->getKeys() as $fromField => $toField) {
				$joinSql .= '`'.$primaryTableAlias.'`.`'.$fromField.'`=`'.$linkTableAlias.'`.`'.$toField."`)\n"; 
			}
			
			$joinSql .= $joinType . ' JOIN `'.$relatedModelName::tableName().'` `'.$relation->getName().'` ON (';		
			
			$moreThanOne = false;
			foreach($relation->getViaKeys() as $fromField => $toField) {
				if($moreThanOne){
					$joinSql .= ' AND ';
				}
				$joinSql .= '`'.$linkTableAlias.'`.`'.$fromField.'`=`'.$relation->getName().'`.`'.$toField.'`'; 
				$moreThanOne = true;
			}
			

		}else
		{
			$joinSql .= $joinType . ' JOIN `'.$relatedModelName::tableName().'` `'.$relation->getName().'` ON (';	
			
			$moreThanOne = false;
			foreach($relation->getKeys() as $fromField => $toField) {
				if($moreThanOne){
					$joinSql .= ' AND ';
				}
				$joinSql .= '`'.$primaryTableAlias.'`.`'.$fromField.'`=`'.$relation->getName().'`.`'.$toField.'`';
				
				$moreThanOne = true;
			}
		}
		
		//soft delete
		if(!$this->getQuery()->getWithDeleted() && $relatedModelName::getColumn('deleted')) {
			$joinSql .= ' AND `'.$relation->getName().'`.`deleted` = false';
		}

		$this->aliasMap[$relation->getName()] = $relatedModelName::getColumns();

		if (isset($relation->query)) {
			$relation->query->tableAlias($relation->getName());

			$this->tableAlias=$relation->getName();
			$this->defaultRecordForEmptyAlias = $relatedModelName;

			$where = $this->buildWhere($relation->query, "\t");//str_replace('`t`', '`' . $relation->getName() . '`', $this->buildWhere($relation->query, "\t"));

			$this->defaultRecordForEmptyAlias = $this->recordClassName;
			$this->tableAlias = $this->getQuery()->getTableAlias();

			if (!empty($where)) {
				$joinSql .= ' AND (' . $where . ')';
			}
		}
		
		if(isset($on)) {
			
			$where = $this->buildWhere($on, "\t");
			
			if (!empty($where)) {
				$joinSql .= ' AND (' . $where . ')';
			}
		}

		$joinSql .= ")";

		$selectCols = null;
		if($selectAttributes) {

			if($relation->hasMany()) {
				throw new Exception("Can't fetch model with joinRelation for a has many relation ".$relation->getName());
			}

			$joinCols = is_array($selectAttributes) ? $selectAttributes : $relatedModelName::getColumns()->getColumnNames();

			foreach ($joinCols as $col) {
				if (!isset($selectCols)) {
					$selectCols = '';
				} else {
					$selectCols .=",\n";
				}
				$selectCols .= "`" . $relation->getName() . '`.`' . $col . '` AS `' . $attributePrefix . $col . '`';
			}
		} 

		

		return [
				'joinSql' => $joinSql, 
				'selectCols' => $selectCols
						];


	}
	
}