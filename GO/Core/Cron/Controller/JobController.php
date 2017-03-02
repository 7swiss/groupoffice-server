<?php

namespace GO\Core\Cron\Controller;

use IFW;
use GO\Core\Controller;
use GO\Core\Cron\Model\Job;
use IFW\Data\Store;
use IFW\Orm\Query;
use IFW\Exception\NotFound;

/**
 * The controller for the job model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class JobController extends Controller {
	
	protected function checkAccess() {
		
		if(!GO()->getAuth()->user()->isAdmin()) {
			return false;
		}
		
		return parent::checkAccess();
	}
	
	
	protected function actionRun() {
		Job::runNext();
		
////		$return = ['success' => true, 'jobs' => []];
////		foreach($jobs as $job) {
////			$return['jobs'][]=$job->toArray();
////		}
//		
//		$this->render($return);
	}
	

	/**
	 * Fetch jobs
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	protected function actionStore($orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "") {

		$query = (new Query())
						->orderBy([$orderColumn => $orderDirection])
						->limit($limit)
						->offset($offset)
						->search($searchQuery, array('t.name'));

		$jobs = Job::find($query);

		$store = new Store($jobs);
		$store->setReturnProperties($returnProperties);

		$this->renderStore($store);
	}

	/**
	 * Get's the default data for a new job
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = "") {

		$user = new Job();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of jobs or fetch a single job
	 *
	 * The attributes of this job should be posted as JSON in a job object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $jobId The ID of the job
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($jobId = null, $returnProperties = "") {
		$job = Job::findByPk($jobId);


		if (!$job) {
			throw new NotFound();
		}

		$this->renderModel($job, $returnProperties);
	}

	/**
	 * Create a new job. Use GET to fetch the default attributes or POST to add a new job.
	 *
	 * The attributes of this job should be posted as JSON in a job object
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

		$job = new Job();
		$job->setValues(GO()->getRequest()->body['data']);
		$job->save();

		$this->renderModel($job, $returnProperties);
	}

	/**
	 * Update a job. Use GET to fetch the default attributes or POST to add a new job.
	 *
	 * The attributes of this job should be posted as JSON in a job object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"jobname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $jobId The ID of the job
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($jobId, $returnProperties = "") {

		$job = Job::findByPk($jobId);

		if (!$job) {
			throw new NotFound();
		}

		$job->setValues(GO()->getRequest()->body['data']);
		$job->save();

		$this->renderModel($job, $returnProperties);
	}

	/**
	 * Delete a job
	 *
	 * @param int $jobId
	 * @throws NotFound
	 */
	public function actionDelete($jobId) {
		$job = Job::findByPk($jobId);

		if (!$job) {
			throw new NotFound();
		}

		$job->delete();

		$this->renderModel($job);
	}

}
