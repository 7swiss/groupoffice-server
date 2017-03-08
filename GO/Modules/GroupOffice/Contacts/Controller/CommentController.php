<?php
namespace GO\Modules\GroupOffice\Contacts\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Contacts\Model\Comment;
use IFW\Orm\Query;
use IFW\Exception\NotFound;

/**
 * The controller for the Comment record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class CommentController extends Controller {


	/**
	 * Fetch comments
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The properties to return to the client. eg. ['\*','emailAddresses.\*']. See {@see \IFW\Orm\Record::toArray()} for more information.
	 * @param string $q See {@see \IFW\Orm\Query::setFromClient()}
	 * @return array JSON Record data
	 */
	protected function actionStore($contactId, $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new Query())

				->limit($limit)
				->offset($offset)
				->search($searchQuery, ['comment.content'])
				->joinRelation('comment')
				->orderBy(['commentId' => 'ASC'])
				->where(['contactId' => $contactId]);
		
				
		if(isset($q)) {
			$query->setFromClient($q);			
		}

		$comments = Comment::find($query);
		$comments->setReturnProperties($returnProperties);

		$this->renderStore($comments);
	}
	
	
	/**
	 * Get's the default data for a new comment
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = ""){
		
		$user = new Comment();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of comments or fetch a single comment
	 *
	 * The attributes of this comment should be posted as JSON in a comment object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $commentId The ID of the comment
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($commentId = null, $returnProperties = "") {	
		$comment = Comment::findByPk($commentId);


		if (!$comment) {
			throw new NotFound();
		}

		$this->renderModel($comment, $returnProperties);
		
	}

	/**
	 * Create a new comment. Use GET to fetch the default attributes or POST to add a new comment.
	 *
	 * The attributes of this comment should be posted as JSON in a comment object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"name":"test",...}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($contactId, $returnProperties = "") {

		$comment = new Comment();
		$comment->setValues(GO()->getRequest()->body['data']);
		$comment->contactId = $contactId;
		$comment->save();

		$this->renderModel($comment, $returnProperties);
	}

	/**
	 * Update a comment. Use GET to fetch the default attributes or POST to add a new comment.
	 *
	 * The attributes of this comment should be posted as JSON in a comment object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"commentname":"test",...}}
	 * </code>
	 * 
	 * @param int $commentId The ID of the comment
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($commentId, $returnProperties = "") {

		$comment = Comment::findByPk($commentId);

		if (!$comment) {
			throw new NotFound();
		}

		$comment->setValues(GO()->getRequest()->body['data']);
		$comment->save();

		$this->renderModel($comment, $returnProperties);
	}

	/**
	 * Delete a comment
	 *
	 * @param int $commentId
	 * @throws NotFound
	 */
	public function actionDelete($commentId) {
		$comment = Comment::findByPk($commentId);

		if (!$comment) {
			throw new NotFound();
		}

		$comment->delete();

		$this->renderModel($comment);
	}
	
	/**
	 * Update multiple comments at once with a PUT request.
	 * 
	 * @example multi delete
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 *	"data" : [{"id" : 1, "markDeleted" : true}, {"id" : 2, "markDeleted" : true}]
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * @throws NotFound
	 */
	public function actionMultiple() {
		
		$response = ['data' => []];
		
		foreach(GO()->getRequest()->getBody()['data'] as $values) {
			
			if(!empty($contactValues['id'])) {
				$comment = Comment::findByPk($values['id']);

				if (!$comment) {
					throw new NotFound();
				}
			}else
			{
				$comment = new Comment();
			}
			
			$comment->setValues($values);
			$comment->save();
			
			$response['data'][] = $comment->toArray();
		}
		
		$this->render($response);
	}
}
