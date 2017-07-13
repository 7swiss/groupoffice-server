<?php
namespace GO\Modules\GroupOffice\Tasks\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Tasks\Model\TaskComment;
use IFW\Exception\NotFound;
use IFW\Orm\Query;
use function GO;


class CommentController extends Controller {


	public function store($taskId, $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {
		
		$query = (new Query())
				->limit($limit)
				->offset($offset)
				->where(['taskId' => $taskId])						
				->joinRelation('comment')
				->orderBy(['commentId' => 'ASC']);
				

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['comment.content']);
		}

		if (!empty($q)) {
			$query->setFromClient($q);
		}
		
		$comments = TaskComment::find($query);
	
		$comments->setReturnProperties($returnProperties);

		$this->renderStore($comments);
	}
	
	
	public function newInstance($taskId, $returnProperties = ""){
		$comment = new TaskComment();
		$comment->taskId = $taskId;
		$this->renderModel($comment, $returnProperties);
	}
	
	

	public function read($commentId, $returnProperties = "*"){
		
		$comment = TaskComment::findByPk($commentId);
		
		if (!$comment) {
			throw new NotFound();
		}

		$this->renderModel($comment, $returnProperties);
	}

	public function create($taskId, $returnProperties = "") {
		
		$comment = new TaskComment();		
		$comment->setValues(GO()->getRequest()->body['data']);
		$comment->taskId = $taskId;
		$comment->save();

		$this->renderModel($comment, $returnProperties);
	}

	public function update($commentId, $returnProperties = "") {

		$comment = TaskComment::findByPk($commentId);

		if (!$comment) {
			throw new NotFound();
		}

		$comment->setValues(GO()->getRequest()->body['data']);
		$comment->save();
		
		$this->renderModel($comment, $returnProperties);
	}

	public function delete($commentId) {
		$comment = TaskComment::findByPk($commentId);

		if (!$comment) {
			throw new NotFound();
		}

		$comment->delete();

		$this->renderModel($comment);
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
				$record = Comment::findByPk($values['id']);

				if (!$record) {
					throw new NotFound();
				}
			}else
			{
				$record = new Comment();
			}
			
			$record->setValues($values);
			$record->save();
			
			$response['data'][] = $record->toArray();
		}
		
		$this->render($response);
	}
	
}