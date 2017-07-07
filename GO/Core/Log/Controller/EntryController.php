<?php

namespace GO\Core\Log\Controller;

use GO\Core\Controller;
use GO\Core\Log\Model\Entry;
use IFW\Data\Filter\FilterCollection;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for the entry model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class EntryController extends Controller {

	public function actionFilters() {
		$this->render($this->getFilterCollection()->toArray());
	}

	private function getFilterCollection() {
		$filters = new FilterCollection(Entry::class);
		$filters->addFilter(\GO\Core\Log\Model\TypeFilter::class);
		$filters->addFilter(\GO\Core\Log\Model\ModuleFilter::class);
		return $filters;
	}

	/**
	 * Fetch entrys
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function actionStore($orderColumn = 'id', $orderDirection = 'DESC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new Query())
						->orderBy([$orderColumn => $orderDirection])
						->limit($limit)
						->offset($offset)
						->search($searchQuery, ['t.description']);

		$this->getFilterCollection()->apply($query);

		if (isset($q)) {
			$query->setFromClient($q);
		}


		$entrys = Entry::find($query);
		$entrys->setReturnProperties($returnProperties);

		$this->renderStore($entrys);
	}

	/**
	 * Get's the default data for a new entry
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function actionNew($returnProperties = "") {

		$user = new Entry();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of entrys or fetch a single entry
	 *
	 * The attributes of this entry should be posted as JSON in a entry object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $entryId The ID of the entry
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionRead($entryId = null, $returnProperties = "") {
		$entry = Entry::findByPk($entryId);


		if (!$entry) {
			throw new NotFound();
		}

		$this->renderModel($entry, $returnProperties);
	}

	/**
	 * Create a new entry. Use GET to fetch the default attributes or POST to add a new entry.
	 *
	 * The attributes of this entry should be posted as JSON in a entry object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = "") {

		$entry = new Entry();
		$entry->setValues(GO()->getRequest()->body['data']);
		$entry->save();

		$this->renderModel($entry, $returnProperties);
	}

	/**
	 * Update a entry. Use GET to fetch the default attributes or POST to add a new entry.
	 *
	 * The attributes of this entry should be posted as JSON in a entry object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"entryname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $entryId The ID of the entry
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($entryId, $returnProperties = "") {

		$entry = Entry::findByPk($entryId);

		if (!$entry) {
			throw new NotFound();
		}

		$entry->setValues(GO()->getRequest()->body['data']);
		$entry->save();

		$this->renderModel($entry, $returnProperties);
	}

	/**
	 * Delete a entry
	 *
	 * @param int $entryId
	 * @throws NotFound
	 */
	public function actionDelete($entryId) {
		$entry = Entry::findByPk($entryId);

		if (!$entry) {
			throw new NotFound();
		}

		$entry->delete();

		$this->renderModel($entry);
	}

}
