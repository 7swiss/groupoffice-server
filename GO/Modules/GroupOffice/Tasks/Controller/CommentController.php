<?php
namespace GO\Modules\GroupOffice\Tasks\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Tasks\Model\TaskComment;
use IFW\Exception\NotFound;
use IFW\Orm\Query;
use function GO;


class CommentController extends Controller {


	public function actionStore($taskId, $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {
		
		$query = (new Query())
				->limit($limit)
				->offset($offset)
				->where(['taskId' => $taskId])						
				->joinRelation('comment')
				->orderBy(['commentId' => 'ASC']);
				

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['description']);
		}

		if (!empty($q)) {
			$query->setFromClient($q);
		}
		
		$comments = TaskComment::find($query);
	
		$comments->setReturnProperties($returnProperties);

		$this->renderStore($comments);
	}
	
	
	protected function actionNew($taskId, $returnProperties = ""){
		$comment = new TaskComment();
		$comment->taskId = $taskId;
		$this->renderModel($comment, $returnProperties);
	}
	
	

	protected function actionRead($commentId, $returnProperties = "*"){
		
		$comment = TaskComment::findByPk($commentId);
		
		if (!$comment) {
			throw new NotFound();
		}

		$this->renderModel($comment, $returnProperties);
	}

	public function actionCreate($taskId, $returnProperties = "") {
		
		$comment = new TaskComment();		
		$comment->setValues(GO()->getRequest()->body['data']);
		$comment->taskId = $taskId;
		$comment->save();

		$this->renderModel($comment, $returnProperties);
	}

	public function actionUpdate($commentId, $returnProperties = "") {

		$comment = TaskComment::findByPk($commentId);

		if (!$comment) {
			throw new NotFound();
		}

		$comment->setValues(GO()->getRequest()->body['data']);
		$comment->save();
		
		$this->renderModel($comment, $returnProperties);
	}

	public function actionDelete($commentId) {
		$comment = TaskComment::findByPk($commentId);

		if (!$comment) {
			throw new NotFound();
		}

		$comment->delete();

		$this->renderModel($comment);
	}
	
}