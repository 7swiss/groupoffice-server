<?php
namespace GO\Core\Templates\Controller;

use GO\Core\Controller;
use GO\Core\Modules\Model\Module;
use GO\Core\Templates\Model\Pdf;
use GO\Core\Templates\Model\PdfRenderer;
use IFW\Exception\NotFound;
use IFW\Orm\Query;
use IFW\Orm\Utils;
use function GO;

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
	public function store($moduleClassName, $orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "") {
		
		$module = Module::find(['name'=>$moduleClassName])->single();

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
	public function newInstance($returnProperties = ""){
		
		$user = new Pdf();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of pdftemplates or fetch a single pdftemplate
	 *
	 * The attributes of this pdftemplate should be posted as JSON in a pdftemplate object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $pdfTemplateId The ID of the pdftemplate
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function read($pdfTemplateId = null, $returnProperties = "") {	
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
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($moduleClassName, $returnProperties = "") {

		$module = Module::find(['name'=>$moduleClassName])->single();

		
		$pdftemplate = new Pdf();
		$pdftemplate->setValues(GO()->getRequest()->body['data']);
		$pdftemplate->setModuleClassName($moduleClassName);
		$pdftemplate->save();

		$this->renderModel($pdftemplate, $returnProperties);
	}

	/**
	 * Update a pdftemplate. Use GET to fetch the default attributes or POST to add a new pdftemplate.
	 *
	 * The attributes of this pdftemplate should be posted as JSON in a pdftemplate object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"pdftemplatename":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $pdfTemplateId The ID of the pdftemplate
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($pdfTemplateId, $returnProperties = "") {

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
	public function delete($pdfTemplateId) {
		$pdftemplate = Pdf::findByPk($pdfTemplateId);

		if (!$pdftemplate) {
			throw new NotFound();
		}

		$pdftemplate->delete();

		$this->renderModel($pdftemplate);
	}
	
	
	public function preview($pdfTemplateId) {
		$pdftemplate = Pdf::findByPk($pdfTemplateId);

		if (!$pdftemplate) {
			throw new NotFound();
		}

		$pdfRenderer = new PdfRenderer($pdftemplate);
		$pdfRenderer->previewMode = true;
		
		GO()->getResponse()->setHeader('Content-Type', 'application/pdf');
		GO()->getResponse()->setHeader('Content-Disposition', 'inline; filename="' . $pdftemplate->name . '.pdf"');
		GO()->getResponse()->setHeader('Content-Transfer-Encoding', 'binary');
		
		
		echo $pdfRenderer->render();
	}
	
	public function duplicate($pdfTemplateId) {
		$pdf = Pdf::findByPk($pdfTemplateId);

		if (!$pdf) {
			throw new NotFound();
		}

		$name = Utils::findUniqueValue($pdf->tableName(), 'name', $pdf->name);
		$duplicate = Utils::duplicate($pdf, ['name' => $name]);
		foreach($pdf->blocks as $block) {
			Utils::duplicate($block, ['pdfTemplateId' => $duplicate->id]);
		}						

		$this->renderModel($duplicate);
	}
	
		/**
	 * Update multiple records at once with a PUT request.
	 * 
	 * @example multi delete
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 *	"data" : [{"id" : 1, "markDeleted" : true}, {"id" : 2, "markDeleted" : true}]
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * @throws NotFound
	 */
	public function multiple() {
		
		$response = ['data' => []];
		
		foreach(GO()->getRequest()->getBody()['data'] as $values) {
			
			if(!empty($values['id'])) {
				$record = Pdf::findByPk($values['id']);

				if (!$record) {
					throw new NotFound();
				}
			}else
			{
				$record = new Pdf();
			}
			
			$record->setValues($values);
			$record->save();
			
			$response['data'][] = $record->toArray();
		}
		
		$this->render($response);
	}
}
