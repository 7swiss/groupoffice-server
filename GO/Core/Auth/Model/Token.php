<?php
namespace GO\Core\Auth\Model;

use DateInterval;
use DateTime;
use GO\Core\GarbageCollection\GarbageCollectionInterface;
use GO\Core\Users\Model\User;
use IFW\Auth\Exception\BadLogin;
use IFW\Auth\Permissions\CreatorOnly;
use IFW\Fs\Folder;
use IFW\Orm\Record;
use function GO;

/**
 * The Token model
 * 
 * Browser authentication works with a token. The server doesn't have session 
 * support because a RESTful API must be stateless. The token identifies the 
 * user. When a user logs in with his username and password the server sends the 
 * token with a HTTPOnly cookie. The HTTPOnly flag is important for security as 
 * it prevents theft by XSS attacks. While this technique protects the token 
 * from XSS attacks it opens up the possibility of cross site request forgery or
 * XSRF attacks. Therefore the server sends a second cookie called XSRFToken 
 * that does not have the HTTPOnly flag set. This cookie must be read by the 
 * client and set as a header called X-XSRFToken or pass it as a GET parameter 
 * "XSRFToken". The header method is preferred but with images in the browser we 
 * can't use the header method. 
 * 
 * Optionally you can disable the token checking in config.php:
 * ```````````````````````````````````````````````````````````````````````````
 * 'GO\Core\Auth\Model\Token' => [
			"checkXSRFToken" => false //Can be convenient to disable in development mode.
		],
 * ```````````````````````````````````````````````````````````````````````````
 * 
 * Authentication starts in {@see \GO\Core\Controller::checkAccess()}. 
 * It calls the {@see \GO\Core\Auth\\GO()->auth()->user()} function that tries to 
 * determine the currently logged in user. This function uses this token model 
 * to authenticate.
 * 
 * @link http://jaspan.com/improved_persistent_login_cookie_best_practice
 *
 * @property User $user The user that belongs to this token. 
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class Token extends Record implements GarbageCollectionInterface {	
	
	
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * The token that identifies the user. Sent in HTTPOnly cookie.
	 * @var string
	 */							
	public $accessToken;

	/**
	 * 
	 * @var int
	 */							
	public $userId;

	/**
	 * Time this token expires. Defaults to one day after the token was created {@see LIFETIME}
	 * @var \DateTime
	 */							
	public $expiresAt;
	
	/**
	 * The remote IP address of the client connecting to the server
	 * 
	 * @var string 
	 */
	public $remoteIpAddress;
	
	/**
	 * The user agent sent by the client
	 * 
	 * @var string 
	 */
	public $userAgent;
	
	
	/**
	 * A date interval for the lifetime of a token
	 * 
	 * @link http://php.net/manual/en/dateinterval.construct.php
	 */
	const LIFETIME = 'P1D';
	
	protected function init() {
		parent::init();
		
		if($this->isNew()) {
			
			
			$this->setClient();
			
			$this->internalRefresh();
		}else
		{
			//update expiry date on every access		
			// Only done on GET auth	
//			$this->setExpiryDate();
//			$this->update();
		}
	}
	
	
	private function setClient() {
		if(isset($_SERVER['REMOTE_ADDR'])) {
			$this->remoteIpAddress = $_SERVER['REMOTE_ADDR'];
		}

		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
		}else if(GO()->getEnvironment()->isCli()) {
			$this->userAgent = 'cli';
		}
	}
	
	protected static function defineRelations() {		
		self::hasOne('user', User::class, ['userId'=>'id']);		
		
		parent::defineRelations();
	}
	
	protected  static function internalGetPermissions() {
		$permissions = new CreatorOnly();
		$permissions->userIdField = 'userId';
		return $permissions;
	}
	
	private static function generateToken(){
		return bin2hex(openssl_random_pseudo_bytes(16));
	}
	
	
	

	/**
	 * Check if the token is expired.
	 * 
	 * @return boolean
	 */
	public function isExpired(){
		
		return $this->expiresAt < new \DateTime();
	}
	
	/**
	 * Get a temporary folder 
	 * 
	 * The folder will be destroyed automatically when the token expires.
	 * 
	 * This folder is accessible via the {@see \GO\Core\Auth\UserProvider}
	 * ```````````````````````````````````````````````````````````````````````````
	 * $tempFolder = IFW::auth()->tempFolder();
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param boolean $autoCreate
	 * @return Folder
	 */
	public function getTempFolder($autoCreate = true){
		$folder = GO()->getConfig()->getTempFolder(false)->getFolder($this->accessToken);
		
		if($autoCreate) {
			$folder->create();
		}
		
		return $folder;
		//$folder->delete();
	}
	
	protected function internalDelete($hard) {
		//clean up temp files
		$this->getTempFolder(false)->delete();
		
		return parent::internalDelete($hard);
	}
	
	
	private function internalRefresh() {
		$this->accessToken = $this->generateToken();
		
		$this->setExpiryDate();
	}
	/**
	 * Set new tokens and expiry date
	 * 
	 * @return Token
	 */
	public function refresh() {
		
		$this->internalRefresh();
		
		return $this->save();
	}
	
	private function setExpiryDate() {
		$expireDate = new DateTime();
		$expireDate->add(new DateInterval(Token::LIFETIME));
		$this->expiresAt = $expireDate;		
	}
	
	
	
	private static $current;
	
	private static function getFromHeader() {
		
		$auth = GO()->getRequest()->getHeader('Authorization');
		if(!$auth) {
			return false;
		}
		preg_match('/Token (.*)/', $auth, $matches);
    if(!isset($matches[1])){
      return false;
    }
		
		return $matches[1];
	}
	
	/**
	 * Get the authorization token by reading the request header "Authorization"
	 * 
	 * @return boolean|self
	 */
	public static function findByRequest(){
		
		if(GO()->getEnvironment()->isCli()) {
			return false;
		}		
		
		if(!isset(self::$current)) {
						
			$tokenStr = self::getFromHeader();
			if(!$tokenStr && GO()->getRequest()->getMethod() == 'GET' && isset($_COOKIE['accessToken'])) {
				$tokenStr = $_COOKIE['accessToken'];
			}

			if(!$tokenStr) {
				return false;
			}
		
			$token = Token::find(['accessToken' => $tokenStr])->single();

			if(!$token) {
				return false;
			}		

			if($token->isExpired()) {
				GO()->debug("Token found but it's expired");
				return false;
			}
			
			self::$current = $token;
		}
		
		return self::$current;
	}
	
	
	public static function getDefaultReturnProperties() {
		//filter out temp folder. We don't want to expose it and also we don't want it to be auto created on every token fetch
		$props =  array_diff(parent::getReadableProperties(), ['validationErrors','modified', 'modifiedAttributes', 'markDeleted', 'tempFolder']);
		
		return implode(',', $props);
	}

	public static function collectGarbage() {
		//cleanup expired tokens
		$tokens = Token::find(['<=', ['expiresAt' => new \DateTime()]]);
		foreach ($tokens as $token) {
			$token->delete();
		}
	}
	
	
	/**
	 * Login by given access token
	 * 
	 * @param string $accessTokenStr
	 * @return self
	 */
	public static function loginByToken($accessTokenStr) {
		$token = GO()->getAuth()->sudo(function() use ($accessTokenStr) {

			$token = Token::find(['accessToken' => $accessTokenStr])->single();
			if (!$token) {
				throw new BadLogin();
			}
			
			$token->setClient();
			$token->save();
			
			return $token;
		});
		
		GO()->getAuth()->setCurrentUser($token->user);
		
		return $token;
	}

}