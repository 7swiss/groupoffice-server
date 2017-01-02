<?php
namespace GO\Core\Users\Controller;

use GO\Core\Users\Model\GroupFilter;
use GO\Core\Users\Model\User;
use GO\Core\Controller;
use GO\Core\Email\Model\Message;
use IFW\Data\Filter\FilterCollection;
use IFW\Exception\NotFound;
use IFW\Orm\Query;



/**
 * The controller for users. Admin group is required.
 * 
 * Uses the {@see User} model.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ForgotPasswordController extends Controller {


	public function run($action, array $routerParams) {
		GO()->getAuth()->sudo(function() use ($action, $routerParams) {
			parent::run($action, $routerParams);
		});
	}
	
	
	private function generateToken(User $user) {
		return md5($user->lastLogin->format('c').$user->password);
	}
	
	/**
	 * 
	 * @param string $email
	 * @throws NotFound
	 */
	public function actionSend($email) {
		
		$user = \GO()->getAuth()->sudo(function() use ($email) {
			return User::find(['OR','LIKE', ['email'=>$email, 'emailSecondary'=>$email]])->single();
		});
		
		if (!$user) {
			throw new NotFound();
		}
		
		$token = $this->generateToken($user);
		
		//example: "Hello {user.username}, Your token is {token}."
		$templateParser = new \IFW\Template\VariableParser();
		$templateParser->addModel('token', $token)
						->addModel('user', $user);
		
		$message = new Message(
 						GO()->getSettings()->smtpAccount, 
 						GO()->getRequest()->getBody()['subject'], 
 						$templateParser->parse(GO()->getRequest()->getBody()['body']),
						'text/plain');
 		
 		$message->setTo($email);
 		
 		$numberOfRecipients = $message->send();

		$this->render(['success' => $numberOfRecipients === 1]);
		
	}
	
	
	public function actionResetPassword($userId, $token) {
		$user = \GO()->getAuth()->sudo(function() use ($userId, $token) {
			$user = User::findByPk($userId);
			
			if(!$user) {
				throw new NotFound();
			}
			
			
			$correctToken = $this->generateToken($user);
			if($token != $correctToken) {
				throw new \IFW\Exception\Forbidden("Token incorrect");
			}
			
			$user->setValues(GO()->getRequest()->getBody()['data']);
			
			$user->save();
			
			return $user;
			
		});
		
		$this->renderModel($user);
		
	}
}