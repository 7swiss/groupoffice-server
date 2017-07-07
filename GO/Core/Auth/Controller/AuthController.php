<?php

namespace GO\Core\Auth\Controller;

use GO\Core\Auth\Model\Token;
use GO\Core\Controller;
use GO\Core\Users\Model\User;
use IFW\Auth\Exception\BadLogin;
use IFW\Exception\Forbidden;
use IFW\Web\Response;

/**
 * The controller that handles authentication
 * 
 * See the {@see \GO\Core\Auth\Model\Token} model for more information
 * about the authentication token.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AuthController extends Controller {

	public function checkAccess() {
		return true;
	}
	
	public function options($route) {
		//Allow all origins		
		

		$this->render();
		
//		echo $route;
	}

	/**
	 * Logs the current user out.
	 * 
	 * @return Response {@see actionLogin()}
	 */
	public function logout() {

		
		$token = Token::findByRequest();
		if ($token) {
			GO()->log(User::LOG_ACTION_LOGOUT, $token->user->username, $token->user);
			$token->unsetCookies();
			$token->delete();
		}
		
		$this->render(['success' => true]);
	}

	/**
	 * Change the current session to another user.
	 * 
	 * Can be used by admins only
	 * 
	 * @param int $userId
	 * @return Response {@see actionLogin()}
	 * @throws Forbidden
	 */
	public function switchTo($userId) {
		if (!GO()->getAuth()->isAdmin()) {
			throw new Forbidden();
		}

		$token = Token::findByRequest();
		$token->user = User::findByPk($userId);
		$token->save();

		$this->renderModel($token, '*,user[*]');
	}

	/**
	 * Logs the current user in.
	 *
	 * <p>Sample JSON post:</p>
	 *
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 * 	"username": "user",
	 * 	"password": "secret"
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 *
	 * @return Response The token
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 *   "data": {
	 *     "user": {
	 *       "id": 1,
	 *       "deleted": false,
	 *       "enabled": true,
	 *       "username": "admin",
	 *       "password": "tJJlUNVIeWo2U",
	 *       "digest": "efb6a865d83ca3d8c7671dd5b81bf3f8",
	 *       "createdAt": "2014-07-21T14:01:17Z",
	 *       "modifiedAt": "2015-09-01T06:54:41Z",
	 *       "loginCount": 134,
	 *       "lastLogin": "2015-09-01T08:54:41Z",
	 *       "isAdmin": true,
	 *       "permissions": {
	 *         "create": true,
	 *         "read": true,
	 *         "update": true,
	 *         "delete": true,
	 *         "manage": true
	 *       },
	 *       "validationErrors": [],
	 *       "className": "GO\Core\Users\Model\User",
	 *       "currentPassword": null,
	 *       "markDeleted": false,
	 *       "contact": {
	 *         "id": 10,
	 *         "deleted": false,
	 *         "userId": 1,
	 *         "createdBy": 1,
	 *         "createdAt": "2015-08-17T14:22:25Z",
	 *         "modifiedAt": "2015-08-17T14:22:25Z",
	 *         "prefixes": "",
	 *         "firstName": "System",
	 *         "middleName": "",
	 *         "lastName": "Administrator",
	 *         "suffixes": "",
	 *         "gender": null,
	 *         "notes": null,
	 *         "isCompany": false,
	 *         "name": "System Administrator",
	 *         "IBAN": "",
	 *         "registrationNumber": "",
	 *         "companyContactId": null,
	 *         "groupId": 2,
	 *         "photo": "http://localhost/groupoffice-server/html/index.php/contacts/10/thumb?modifiedAt=null",
	 *         "permissions": {
	 *           "create": true,
	 *           "read": true,
	 *           "update": true,
	 *           "delete": true,
	 *           "manage": true
	 *         },
	 *         "validationErrors": [],
	 *         "className": "GO\Modules\Contacts\Model\Contact",
	 *         "markDeleted": false
	 *       }
	 *     },
	 *     "accessToken": "4cd17e1ad06fcfffb1b94af14ab56db458c06794",
	 *     "XSRFToken": "8afaf469a0a631076a48d711163068039bec165f",
	 *     "expiresAt": "2015-09-02T08:54:57Z",
	 *     "userId": 1,
	 *     "permissions": {
	 *       "create": true,
	 *       "read": true,
	 *       "update": true,
	 *       "delete": true,
	 *       "manage": true
	 *     },
	 *     "validationErrors": [],
	 *     "className": "GO\Core\Auth\Model\Token",
	 *     "checkXSRFToken": false,
	 *     "markDeleted": false
	 *   },
	 *   "success": true
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 */
	public function login($returnProperties = '*,user[*]') {

		$user = User::login(
										GO()->getRequest()->body['data']['username'], GO()->getRequest()->body['data']['password'], true);

		if (!$user) {
			throw new BadLogin();
		}

		$token = new Token();
		$token->user = $user;
		$token->save();

		$token->setCookies();


		$this->renderModel($token, $returnProperties);
	}

	public function loginByToken($token, $returnProperties = '*,user[*]') {
		$accessToken = Token::loginByToken($token);

		$this->renderModel($accessToken, $returnProperties);
	}

	/**
	 * Check if there's an active session
	 * 
	 * @return Response {@see actionLogin()}
	 */
	public function isLoggedIn($returnProperties = '*,user[*]') {
		
		if(!\GO\Core\Install\Model\System::isDatabaseInstalled()) {
			throw new \IFW\Exception\HttpException(503);
		}
		
		
		$token = GO()->getAuth()->sudo(function() {

			$token = Token::findByRequest();


//			if($token && $token->user->password == null) {
//				$token->delete();
//				
//				GO()->debug("Token of user '".$token->user->username."' was destroyed because it has no password set");
//				return null;
//			}

			return $token;
		});

		if ($token) {
			$token->refresh();
			$token->setCookies();
			$this->renderModel($token, $returnProperties);
		} else {
			throw new \IFW\Exception\NotFound();
		}
	}

}
