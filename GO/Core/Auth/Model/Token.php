<?php
namespace GO\Core\Auth\Model;

use DateInterval;
use DateTime;
use Exception;
use GO\Core\Users\Model\User;
use IFW;
use IFW\Auth\Permissions\CreatorOnly;
use IFW\Fs\Folder;
use IFW\Orm\Record;

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

class Token extends Record {	
	
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
	 * The extra token that must be set as a header "X-XSRFToken" or GET parameter "XSRFToken" to prevent XSRF attacks.
	 * @var string
	 */							
	public $XSRFToken;

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
	 * You can disable this in development environments where you want to be able
	 * to easily test requests.
	 * 
	 * @var boolean 
	 */
	public $checkXSRFToken = true;
	
	/**
	 * A date interval for the lifetime of a token
	 * 
	 * @link http://php.net/manual/en/dateinterval.construct.php
	 */
	const LIFETIME = 'P1D';
	
	protected function init() {
		parent::init();
		
		if($this->isNew()) {
			$this->refresh();
		}else
		{
			//update expiry date on every access			
			$this->setExpiryDate();
			$this->update();
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
		
		//openssl has broader support than mcrypt
		return bin2hex(openssl_random_pseudo_bytes(16));
//		$randomData = mcrypt_create_iv(20, MCRYPT_DEV_URANDOM);
//		if ($randomData !== false && strlen($randomData) === 20) {
//			return bin2hex($randomData);
//		}
    
		throw new Exception("We need mcrypt support in PHP!");
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function internalSave() {
		
		$ret = parent::internalSave();
		
		if($ret) {
			
			//clean garbage in 10% of the logins
			if (rand(1, 10) === 1) {
				$this->collectGarbage();
			}
		}
		
		return $ret;
	}
	
	private function collectGarbage() {
		
		\GO()->getAuth()->sudo(function() {		
			$tokens = Token::find(['<=', ['expiresAt' => gmdate('Y-m-d H:i:s', time())]]);

			foreach ($tokens as $token) {
				$token->delete();
			}
		});
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
		$folder->folderCreateMode = 0777;
		
		if($autoCreate){
			$folder->create();
		}
		
		return $folder;
		//$folder->delete();
	}
	
	protected function internalDelete($hard) {
		//clean up temp files
		$this->getTempFolder()->delete();
		
		return parent::internalDelete($hard);
	}
	
	/**
	 * Set's the token cookies
	 */
	public function setCookies() {				
		//Should be httpOnly so XSS exploits can't access this token
		setcookie('accessToken', $this->accessToken, 0, "/", null, false, true);
		
		//XSRF is NOT httpOnly because it has to be added by the browser as a header
		setcookie('XSRFToken', $this->XSRFToken, 0, "/", null, false, false);		
	}
	
	
	/**
	 * Set new tokens and expiry date
	 * 
	 * @return \GO\Core\Auth\Model\Token
	 */
	public function refresh() {
		
		$this->accessToken = $this->generateToken();
		$this->XSRFToken = $this->generateToken();			
		
		$this->setExpiryDate();
		
		return $this;
	}
	
	private function setExpiryDate() {
		$expireDate = new DateTime();
		$expireDate->add(new DateInterval(Token::LIFETIME));
		$this->expiresAt = $expireDate;		
	}
	
	/**
	 * Unsets the token cookies
	 */
	public function unsetCookies(){
		
		//Should be httpOnly so XSS exploits can't access this token
		setcookie('accessToken', NULL, 0, "/", null, false, true);
		
		//XSRF is NOT httpOnly because it has to be added by the browser as a header
		setcookie('XSRFToken', NULL, 0, "/", null, false, false);
	}
	
	
	private static function requestXSRFToken(){
		if(isset($_GET['XSRFToken'])) {
			return $_GET['XSRFToken'];
		}
		if(isset(GO()->getRequest()->headers['x-xsrftoken'])) {
			return GO()->getRequest()->headers['x-xsrftoken'];
		}
		
		return false;
	}
	
	private static $current;
	
	/**
	 * Get the user by token cookie
	 * 
	 * Also check expiration and XSRFToken.
	 * 
	 * @param boolean $checkXSRFToken 
	 * @return boolean|self
	 */
	public static function findByCookie(){
		
		if(!isset($_COOKIE['accessToken'])) {
			return false;
		}
		
		
		if(!isset(self::$current)) {
		
			$token = Token::find(['accessToken' => $_COOKIE['accessToken']])->single();

			if(!$token) {
				return false;
			}		

			if($token->isExpired()) {
				GO()->debug("Token found but it's expired");
				return false;
			}
			
			self::$current = $token;
		}
		
		//remove cookie as header has been set.
		//Small security improvement as this token will not be accessible trough document.cookies anymore.
		//It's still somewhere in javascript but a little bit harder to get.
//		if(isset($_COOKIE['XSRFToken'])) {
//			setcookie('XSRFToken', null, 0, '/', $_SERVER['HTTP_HOST'], false, false);
//		}
		
		return self::$current;
	}
	
	/**
	 * Verify the XSRF token
	 * 
	 * @return boolean
	 */
	public function checkXSRF() {
		return !$this->checkXSRFToken || self::requestXSRFToken() === $this->XSRFToken;
	}
}