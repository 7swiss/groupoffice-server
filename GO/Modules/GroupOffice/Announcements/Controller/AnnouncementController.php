<?php
namespace GO\Modules\GroupOffice\Announcements\Controller;

use IFW;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Orm\Query;
use IFW\Exception\NotFound;
use GO\Modules\GroupOffice\Announcements\Model\Announcement;

/**
 * The controller for the announcement model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AnnouncementController extends Controller {


	/**
	 * Fetch announcements
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function store($limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "") {

		$query = (new Query())
				->orderBy(['id' => 'DESC'])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, array('t.title','t.text'));

		$announcements = Announcement::find($query);
		$announcements->setReturnProperties($returnProperties);
		$this->renderStore($announcements);
	}
	
	
	/**
	 * Get's the default data for a new announcement
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function newInstance($returnProperties = ""){
		
		$user = new Announcement();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of announcements or fetch a single announcement
	 *
	 * The attributes of this announcement should be posted as JSON in a announcement object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $announcementId The ID of the announcement
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function read($announcementId = null, $returnProperties = "") {	
		$announcement = Announcement::findByPk($announcementId);


		if (!$announcement) {
			throw new NotFound();
		}

		$this->renderModel($announcement, $returnProperties);
		
	}

	/**
	 * Create a new announcement. Use GET to fetch the default attributes or POST to add a new announcement.
	 *
	 * The attributes of this announcement should be posted as JSON in a announcement object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($returnProperties = "") {

		$announcement = new Announcement();
		$announcement->setValues(GO()->getRequest()->body['data']);
		$announcement->save();

		$this->renderModel($announcement, $returnProperties);
	}

	/**
	 * Update a announcement. Use GET to fetch the default attributes or POST to add a new announcement.
	 *
	 * The attributes of this announcement should be posted as JSON in a announcement object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"announcementname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $announcementId The ID of the announcement
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($announcementId, $returnProperties = "") {

		$announcement = Announcement::findByPk($announcementId);

		if (!$announcement) {
			throw new NotFound();
		}

		$announcement->setValues(GO()->getRequest()->body['data']);
		$announcement->save();

		$this->renderModel($announcement, $returnProperties);
	}

	/**
	 * Delete a announcement
	 *
	 * @param int $announcementId
	 * @throws NotFound
	 */
	public function delete($announcementId) {
		$announcement = Announcement::findByPk($announcementId);

		if (!$announcement) {
			throw new NotFound();
		}

		$announcement->delete();

		$this->renderModel($announcement);
	}
}
