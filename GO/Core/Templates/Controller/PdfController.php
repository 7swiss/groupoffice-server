<?php
namespace GO\Core\Templates\Controller;

use GO\Core\Controller;
use GO\Core\Templates\Model\Pdf;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for the pdftemplate model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class PdfController extends Controller {


	/**
	 * Fetch PDF templates
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	protected function actionStore($moduleClassName, $orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "") {
		
		$module = \GO\Core\Modules\Model\Module::find(['name'=>$moduleClassName])->single();

		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, ['t.name'])
				->where(['moduleId' => $module->id]);

		$pdftemplates = Pdf::find($query);
		$pdftemplates->setReturnProperties($returnProperties);

		$this->renderStore($pdftemplates);
	}
	
	
	/**
	 * Get's the default data for a new pdftemplate
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = ""){
		
		$user = new Pdf();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of pdftemplates or fetch a single pdftemplate
	 *
	 * The attributes of this pdftemplate should be posted as JSON in a pdftemplate object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $pdfTemplateId The ID of the pdftemplate
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($pdfTemplateId = null, $returnProperties = "") {	
		$pdftemplate = Pdf::findByPk($pdfTemplateId);


		if (!$pdftemplate) {
			throw new NotFound();
		}

		$this->renderModel($pdftemplate, $returnProperties);
		
	}

	/**
	 * Create a new pdftemplate. Use GET to fetch the default attributes or POST to add a new pdftemplate.
	 *
	 * The attributes of this pdftemplate should be posted as JSON in a pdftemplate object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($moduleClassName, $returnProperties = "") {

		$module = \GO\Core\Modules\Model\Module::find(['name'=>$moduleClassName])->single();

		
		$pdftemplate = new Pdf();
		$pdftemplate->setValues(GO()->getRequest()->body['data']);
		$pdftemplate->moduleId = $module->id;
		$pdftemplate->save();

		$this->renderModel($pdftemplate, $returnProperties);
	}

	/**
	 * Update a pdftemplate. Use GET to fetch the default attributes or POST to add a new pdftemplate.
	 *
	 * The attributes of this pdftemplate should be posted as JSON in a pdftemplate object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"pdftemplatename":"test",...}}}
	 * </code>
	 * 
	 * @param int $pdfTemplateId The ID of the pdftemplate
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($pdfTemplateId, $returnProperties = "") {

		$pdftemplate = Pdf::findByPk($pdfTemplateId);

		if (!$pdftemplate) {
			throw new NotFound();
		}

		$pdftemplate->setValues(GO()->getRequest()->body['data']);
		$pdftemplate->save();

		$this->renderModel($pdftemplate, $returnProperties);
	}

	/**
	 * Delete a pdftemplate
	 *
	 * @param int $pdfTemplateId
	 * @throws NotFound
	 */
	public function actionDelete($pdfTemplateId) {
		$pdftemplate = Pdf::findByPk($pdfTemplateId);

		if (!$pdftemplate) {
			throw new NotFound();
		}

		$pdftemplate->delete();

		$this->renderModel($pdftemplate);
	}
}
