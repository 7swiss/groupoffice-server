<?php
namespace GO\Core\Notifications\Controller;

use DateTime;
use Exception;
use GO\Core\Controller;
use GO\Core\Notifications\Model\Notification;
use GO\Core\Notifications\Model\Watch;
use GO\Core\Orm\Model\RecordType;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for the notification model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class NotificationController extends Controller {


	/**
	 * Fetch notifications
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	protected function actionStore($returnProperties = "") {

		$notifications = $this->getAllNotifications();
		
		$notifications->setReturnProperties($returnProperties);

		$this->renderStore($notifications);
	}
	
	private function getAllNotifications() {
		$currentUserId = GO()->getAuth()->user()->id();
						
		$query = (new Query())
				->distinct()				
				->where(['for.groupUsers.userId' => $currentUserId])
				->joinRelation('appearances', false, 'LEFT', ['appearances.userId' => $currentUserId])						
				->andWhere(['>',['expiresAt' => new DateTime()]])
				->andWhere(['appearances.userId'=>null]);

		return Notification::find($query);
	}
	
	
	public function actionDismiss($notificationId, $userId) {
		$notification = Notification::findByPk($notificationId);
		
		if(!$notification) {
			throw new NotFound();
		}
		
		$success = $notification->dismiss($userId);
		
		$this->render(['success' => $success]);
	}
	
	public function actionDismissAll($userId) {
		$notifications = $this->getAllNotifications();
		
		foreach($notifications as $notification)
		{
			if(!$notification->dismiss($userId)) {
				throw new Exception("Failed to dismiss notification");
			}
		}
		
		$this->render(['success' => true]);
	}
	
	
	public function actionIsWatched($recordClassName, $recordId, $userId) {
		$recordType = RecordType::find(['name' => $recordClassName])->single();
		
		if(!$recordType) {
			throw new NotFound();
		}
		
		$groupId = \GO\Core\Users\Model\User::findByPk($userId)->group->id;
		
		$watch = Watch::findByPk(['recordTypeId' => $recordType->id, 'recordId' => $recordId, 'groupId'=>$groupId]);		
		
		return $this->render(['isWatched'=>$watch != false]);
	}
	
	public function actionWatch($recordClassName, $recordId, $userId) {
		$recordType = RecordType::find(['name' => $recordClassName])->single();
		
		if(!$recordType) {
			throw new NotFound();
		}
		
		$groupId = \GO\Core\Users\Model\User::findByPk($userId)->group->id;
		
		$watch = Watch::findByPk(['recordTypeId' => $recordType->id, 'recordId' => $recordId, 'groupId'=>$groupId]);
		if(!$watch) {
			$watch = new Watch();
			$watch->recordTypeId = $recordType->id;
			$watch->recordId = $recordId;
			$watch->groupId = $groupId;
			$watch->save();			
		}		
		
		return $this->renderModel($watch);
	}
	
	public function actionUnwatch($recordClassName, $recordId, $userId) {
		$recordType = RecordType::find(['name' => $recordClassName])->single();
		
		if(!$recordType) {
			throw new NotFound();
		}
		
		$groupId = \GO\Core\Users\Model\User::findByPk($userId)->group->id;
		
		$watch = Watch::findByPk(['recordTypeId' => $recordType->id, 'recordId' => $recordId, 'groupId'=>$groupId]);
		if($watch) {			
			$watch->delete();			
		}
		
		
		return $this->renderModel($watch);
	}
}
