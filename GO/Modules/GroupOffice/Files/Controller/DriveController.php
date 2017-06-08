<?php

namespace GO\Modules\GroupOffice\Files\Controller;

use IFW;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Files\Model\Drive;
use GO\Modules\GroupOffice\Files\Model\Mount;
use IFW\Orm\Query;

/**
 * The controller for calendars
 *
 * See {@see Event} model for the available properties

 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class DriveController extends Controller {

	public function actionStore($orderColumn = 't.name', $orderDirection = 'ASC', $limit = 20, $offset = 0, $searchQuery = "", $returnProperties = "*,owner[name]", $q = null) {
		$query = (new Query)
				  ->select("t.*, CASE WHEN m.userId IS NULL THEN 0 ELSE 1 END as isMounted")
				  ->joinRelation('owner', 'name')
				  ->join(Mount::tableName(),'m','t.id = m.driveId AND m.userId = '.GO()->getAuth()->user()->id, 'LEFT')
				  ->limit($limit)
				  ->offset($offset);

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['name']);
		}
		if(isset($q)) {
			$query->setFromClient($q);
		}
	
		$nodes = Drive::find($query);
		$nodes->setReturnProperties($returnProperties);

		$this->responseData['path'] = [];
		
		$this->renderStore($nodes);

	}

	protected function actionRead($id, $returnProperties = "*,groups") {

		if($id === 'home')
			$drive = Drive::home();
		else
			$drive = Drive::findByPk($id);

		if (!$drive) {
			throw new NotFound();
		}

		$this->renderModel($drive, $returnProperties);
	}

	public function actionCreate($returnProperties = "*") {

		$data = IFW::app()->getRequest()->body['data'];
		$drive = new Drive();
		$drive->setValues($data);
		$drive->save();

		$this->renderModel($drive, $returnProperties);
	}

	public function actionUpdate($id, $returnProperties = "*,groups") {

		$drive = Drive::findByPk($id);

		if (!$drive) {
			throw new NotFound();
		}

		$drive->setValues(IFW::app()->getRequest()->body['data']);
		$drive->save();

		$this->renderModel($drive, $returnProperties);
	}

	public function actionMount($id, $mount = true) {
		$drive = Drive::findByPk($id);
		if(empty($drive)) {
			throw new \IFW\Exception\NotFound();
		}
		$success = $mount ? $drive->mount()->save() : $drive->unmount() ;
		$this->render(['success'=>$success]);
	}

	public function actionMountStore() {
		$query = (new Query())
				  ->join(Mount::tableName(),'m','t.id = m.driveId AND m.userId = '.GO()->getAuth()->user()->id)
				  ->joinRelation('root')
				  ->where('m.userId = '.GO()->getAuth()->user()->id);

		$mountedDrives = Drive::find($query);
		$all = $mountedDrives->all();
		$home = Drive::home();
		$this->responseData['home'] = $home->id;
		$all[] = $home;

		$this->renderStore($all);
	}
}