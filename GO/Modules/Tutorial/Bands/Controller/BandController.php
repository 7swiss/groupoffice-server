<?php

namespace GO\Modules\Tutorial\Bands\Controller;

use GO\Core\Controller;
use GO\Core\CustomFields\Model\Field;
use IFW;
use IFW\Data\Filter\FilterCollection;
use IFW\Exception\NotFound;
use IFW\Orm\Query;
use GO\Modules\Tutorial\Bands\Model\GenreFilter;
use GO\Modules\Tutorial\Bands\Model\Band;
use GO\Modules\Tutorial\Bands\Model\BandCustomFields;

/**
 * The controller for bands. Admin group is required.
 * 
 * Uses the {@see Band} model.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class BandController extends Controller {

	/**
	 * Fetch bands
	 *
	 * @param StringUtil $orderColumn Order by this column
	 * @param StringUtil $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param StringUtil $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @param StringUtil $where {@see \IFW\Db\Criteria::whereSafe()}
	 * @return array JSON Model data
	 */
	protected function actionStore($orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $where = null) {

		$query = (new Query())
						->orderBy([$orderColumn => $orderDirection])
						->limit($limit)
						->offset($offset)
						->debug();

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['t.name']);
		}

		if (!empty($where)) {

			$where = json_decode($where, true);

			if (count($where)) {
				$query
								->groupBy(['t.id'])
								->whereSafe($where);
			}
		}
		
		Band::createFilterCollection()->apply($query);		

		$bands = Band::find($query);
		$bands->setReturnProperties($returnProperties);

		$this->renderStore($bands);
	}
	
	public function actionFilters() {
		$this->render(Band::createFilterCollection()->toArray());		
	}
	
	/**
	 * GET a list of bands or fetch a single band
	 *
	 * @todo returnProperties in render and what about the actionNew and actionCreate? There's no store to set returnProperties on.
	 * @param int $bandId The ID of the group
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($bandId = null, $returnProperties = '*,albums') {

		$store = Band::find(['id' => $bandId]);
		$store->setReturnProperties($returnProperties);
		$band = $store->single();

		if (!$band) {
			throw new NotFound();
		}

		$this->renderModel($band, $returnProperties);
	}

	/**
	 * Get's the default data for a new band
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = '*,albums') {

		//Check edit permission		
		$band = new Band();

		$this->renderModel($band, $returnProperties);
	}

	/**
	 * Create a new band. Use GET to fetch the default attributes or POST to add a new band.
	 *
	 * The attributes of this band should be posted as JSON in a band object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"bandname":"test",...}}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = '*,albums') {

		$band = new Band();
		$band->setValues(IFW::app()->getRequest()->body['data']);
		$band->save();

		$this->renderModel($band, $returnProperties);
	}

	/**
	 * Update a band. Use GET to fetch the default attributes or POST to add a new band.
	 *
	 * The attributes of this band should be posted as JSON in a band object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"bandname":"test",...}}}
	 * </code>
	 * 
	 * @param int $bandId The ID of the band
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($bandId, $returnProperties = '*,albums') {

		$band = Band::findByPk($bandId);

		if (!$band) {
			throw new NotFound();
		}

		$band->setValues(IFW::app()->getRequest()->body['data']);
		$band->save();

		$this->renderModel($band, $returnProperties);
	}

	/**
	 * Delete a band
	 *
	 * @param int $bandId
	 * @throws NotFound
	 */
	public function actionDelete($bandId) {
		$band = Band::findByPk($bandId);

		if (!$band) {
			throw new NotFound();
		}

		$band->delete();

		$this->renderModel($band);
	}

}
