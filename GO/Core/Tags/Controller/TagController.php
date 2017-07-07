<?php

namespace GO\Core\Tags\Controller;

use IFW;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Orm\Query;
use IFW\Exception\NotFound;
use GO\Core\Tags\Model\Tag;

/**
 * The controller for address books
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class TagController extends Controller {


	/**
	 * Fetch tags
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function store($recordClassName=null,$orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "") {
		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, ['name']);

		//Join contact link model for example and count items
		if (isset($recordClassName)) {
			$tags = Tag::findForRecordClass($recordClassName, $query);
		}else
		{
			$tags = Tag::find($query);
		}

		$tags->setReturnProperties($returnProperties);

		$this->renderStore($tags);
	}
	
	/**
	 * Create a new tag. Use GET to fetch the default attributes or POST to add a new tag.
	 *
	 * The attributes of this tag should be posted as JSON in a tag object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"tag":{"attributes":{"tagname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function newInstance($returnProperties = "") {

		$tag = new Tag();

		$this->renderModel($tag, $returnProperties);
	}

	/**
	 * Create a new tag. Use GET to fetch the default attributes or POST to add a new tag.
	 *
	 * The attributes of this tag should be posted as JSON in a tag object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"tag":{"attributes":{"tagname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($returnProperties = "") {

		$tag = new Tag();

		$tag->setValues(GO()->getRequest()->body['data']);
		$tag->save();

		$this->renderModel($tag, $returnProperties);
	}
	
	public function read($tagId, $returnProperties = ""){
		$tag = Tag::findByPk($tagId);

		if (!$tag) {
			throw new NotFound();
		}
		
		$this->renderModel($tag, $returnProperties);
	}

	/**
	 * Update a tag. Use GET to fetch the default attributes or POST to add a new tag.
	 *
	 * The attributes of this tag should be posted as JSON in a tag object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"tag":{"attributes":{"tagname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $tagId The ID of the tag
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($tagId, $returnProperties = "") {

		$tag = Tag::findByPk($tagId);

		if (!$tag) {
			throw new NotFound();
		}

		$tag->setValues(GO()->getRequest()->body['data']);
		$tag->save();

		$this->renderModel($tag, $returnProperties);
	}

	/**
	 * Delete a tag
	 *
	 * @param int $tagId
	 * @throws NotFound
	 */
	public function delete($tagId) {
		$tag = Tag::findByPk($tagId);

		if (!$tag) {
			throw new NotFound();
		}

		$tag->delete();

		$this->renderModel($tag, $returnProperties);
	}

}
