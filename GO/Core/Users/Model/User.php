<?php

namespace GO\Core\Users\Model;

use DateTime;
use Exception;
use GO\Core\Auth\Model\Token;
use GO\Core\Model\Session;
use GO\Core\Orm\Record;
use GO\Core\Users\Model\UserPermissions;
use GO\Modules\Contacts\Model\Contact;
use IFW;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;
use IFW\Validate\ValidatePassword;

/**
 * User model
 *
 * 
 * @property Contact $contact
 *
 * @property Group[] $groups The groups of the user is a member off.
 * @property Group $group The group of the user. Every user get's it's own group for sharing.
 * @property Session[] $sessions The sessions of the user.
 * @property Token[] $tokens The authentication tokens of the user.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class User extends Record implements UserInterface, \GO\Core\Email\Model\RecipientInterface{

	/**
	 * Primary key of the model.
	 * @var int
	 */
	public $id;

	/**
	 * 
	 * @var bool
	 */
	public $deleted = false;

	/**
	 * Disables the user from logging in
	 * @var bool
	 */
	public $enabled = true;

	/**
	 * 
	 * @var string
	 */
	public $username;

	/**
	 * If the password hash is set to null it's impossible to login.
	 * @var string
	 */
	protected $password;

	/**
	 * Digest of the password used for digest auth. (Deprecated?)
	 * @var string
	 */
	protected $digest;

	/**
	 * 
	 * @var \DateTime
	 */
	public $createdAt;

	/**
	 * 
	 * @var \DateTime
	 */
	public $modifiedAt;

	/**
	 * 
	 * @var int
	 */
	public $loginCount = 0;

	/**
	 * 
	 * @var \DateTime
	 */
	public $lastLogin;

	/**
	 * E-mail address of the user. The system uses this for notifications.
	 * @var string
	 */
	public $email;

	/**
	 * E-mail address of the user. The system uses this for password recovery.
	 * @var string
	 */
	public $emailSecondary;

	/**
	 * 
	 * @var string
	 */
	public $photoBlobId;

	const LOG_ACTION_LOGIN = 'login';
	const LOG_ACTION_LOGOUT = 'logout';

	/**
	 * Fires before login
	 * 
	 * @param string $username
	 * @param string $password
	 * @param boolean $count
	 */
	const EVENT_BEFORE_LOGIN = 0;

	/**
	 * Fires after successful login
	 * 
	 * @param User $user
	 */
	const EVENT_AFTER_LOGIN = 1;

	/**
	 * Non admin users must verify their password before they can set the password.
	 * 
	 * @var boolean 
	 */
	private $passwordVerified;

	/**
	 * Cache value for isAdmin()
	 * 
	 * @var bool 
	 */
	private $isAdmin;

	/**
	 *
	 * {@inheritdoc}
	 */
	protected static function defineValidationRules() {
		return [
//				new ValidatePassword('password'),
				new \IFW\Validate\ValidateEmail('email'),
				new \IFW\Validate\ValidateEmail('emailSecondary')
		];
	}

	public static function getTable() {
		parent::getTable()->getColumn('password')->trimInput = false;

		return parent::getTable();
	}

	protected static function internalGetPermissions() {
		return new UserPermissions();
	}

	public function id() {
		return $this->id;
	}

	public static function tableName() {
		return 'auth_user';
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public static function defineRelations() {

		self::hasMany('groups', Group::class, ["id" => "userId"])
						->via(UserGroup::class, ['groupId' => 'id']);

		self::hasMany('userGroup', UserGroup::class, ['id' => "userId"]);
		
		self::hasOne('group', Group::class, ['id' => 'userId']);
		self::hasMany('tokens', Token::class, ["id" => "userId"]);

		parent::defineRelations();
	}

	/**
	 * Logs a user in.
	 *
	 * @param string $username
	 * @param string $password
	 * @return User|bool
	 */
	public static function login($username, $password, $count = true) {

		if (self::fireStaticEvent(self::EVENT_BEFORE_LOGIN, $username, $password, $count) === false) {
			return false;
		}

		$user = GO()->getAuth()->sudo(function() use ($username) {
			return User::find(['username' => $username])->single();
		});

		$success = true;

		if (!$user) {
			$success = false;
		} elseif (!$user->enabled) {
			GO()->debug("LOGIN: User " . $username . " is disabled");
			$success = false;
		} elseif (!$user->checkPassword($password)) {
			GO()->debug("LOGIN: Incorrect password for " . $username);
			$success = false;
		}

		$str = "LOGIN ";
		$str .= $success ? "SUCCESS" : "FAILED";
		$str .= " for user: \"" . $username . "\" from IP: ";

		if (isset($_SERVER['REMOTE_ADDR'])) {
			$str .= $_SERVER['REMOTE_ADDR'];
		} else {
			$str .= 'unknown';
		}

		if (!$success) {
			return false;
		} else {
			GO()->getAuth()->setCurrentUser($user);
			if ($count) {
				$user->loginCount++;
				$user->lastLogin = new DateTime();
				if (!$user->save()) {
					throw new Exception("Could not save user in login");
				}
			}

			self::fireStaticEvent(self::EVENT_AFTER_LOGIN, $user);

			return $user;
		}
	}

	public function internalValidate() {

		if (!empty($this->password) && $this->isModified('password')) {
			$this->digest = md5($this->username . ":" . GO()->getConfig()->productName . ":" . $this->password);
			$this->password = password_hash($this->password, PASSWORD_DEFAULT);
		}

		return parent::internalValidate();
	}

	private function logSave() {
		if ($this->isModified('loginCount')) {
			GO()->log(self::LOG_ACTION_LOGIN, $this->username, $this);
		} else if ($this->isModified()) {
			$logAction = $this->isNew() ? self::LOG_ACTION_UPDATE : self::LOG_ACTION_UPDATE;
			GO()->log($logAction, $this->username, $this);
		}
	}

	protected function internalSave() {
		$wasNew = $this->isNew();

		$this->logSave();

		$success = parent::internalSave();

		if ($success && $wasNew) {

			//Create a group for this user and add the user to this group.
			$group = new Group();
			$group->userId = $this->id;
			$group->name = $this->username;
			if(!$group->save()) {
				throw new Exception("Could not save user group");
			}

			$ur = new UserGroup();
			$ur->userId = $this->id;
			$ur->groupId = $group->id;
			if(!$ur->save()) {
				throw new Exception("Could not save user group");
			}
		}

		return $success;
	}

	protected function internalDelete($hard) {
		if ($this->id === 1) {
			throw new \IFW\Exception\Forbidden("Admin can't be deleted!");
		}


		return parent::internalDelete($hard);
	}

	public function setPassword($password) {
		if (GO()->getAuth()->user()->isAdmin() || $this->passwordVerified) {
			$this->password = $password;
		} else {
			throw new IFW\Exception\Forbidden();
		}
	}

	/**
	 * Check if the password is correct for this user.
	 *
	 * @param string $password
	 * @return boolean
	 */
	public function checkPassword($password) {

		$hash = $this->isModified('password') ? $this->getOldAttributeValue('password') : $this->password;

		$this->passwordVerified = password_verify($password, $hash);

		return $this->passwordVerified;
	}

	/**
	 * Check if this user is in the admins group
	 *
	 * @return bool
	 */
	public function isAdmin() {

		if (!isset($this->isAdmin)) {
			$ur = UserGroup::findByPk(['userId' => $this->id, 'groupId' => Group::ID_ADMINS]);
			$this->isAdmin = $ur !== false;
		}

		return $this->isAdmin;
	}

	//for API
	public function getIsAdmin() {
		return $this->isAdmin();
	}

	/**
	 * Checks if the given user is member of a group this user is also a member of.
	 * 
	 * @param self $user
	 * @return boolean
	 */
	public function isInSameGroup(self $user) {

		return UserGroup::find(
										(new Query())
														->select('1')
														->joinRelation('groupUsers')
														->andWhere(['!=', ['groupId' => \GO\Core\Users\Model\Group::ID_INTERNAL]])
														->andWhere(['userId' => $this->id])
														->andWhere(['groupUsers.userId' => $user->id])
						)->single();
	}

	/**
	 * Used for lost password to verify e-mail link
	 * 
	 * @return string
	 */
	public function generateToken() {
		return md5($this->lastLogin->format('c') . $this->password);
	}
	
	public function getModules() {
				
		$ret = [];
		
		$modules = GO()->getModules();		
		foreach($modules as $moduleName) {
			GO()->getAuth()->sudo(function() use (&$ret, $moduleName) {

				$ret[] = (new $moduleName)->toArray('name,permissions,capabilities');

			}, $this);
		}

		return $ret;
	}
	
	
	/**
	 * Create a token to use for login without password
	 * 
	 * @return Token
	 */
	public function createLoginToken() {
		
		
		return GO()->getAuth()->sudo(function() {
			$token = \GO\Core\Auth\Model\Token::find(['userId' => $this->id, 'userAgent' => null])->single();
		
			if(!$token) {			
				$token = new \GO\Core\Auth\Model\Token();
				$token->user = $this;
			}
			$token->expiresAt = new \IFW\Util\DateTime();
			$token->expiresAt->modify("+1 month");

			$token->userAgent = null;
			$token->remoteIpAddress = null;
			$token->save();

			return $token;
		});
	}

	/**
	 * For {@see \GO\Core\Email\Model\RecipientInterface}
	 */
	public static function findRecipients($searchQuery, $limit, $foundEmailAddresses = array()) {
		$query = (new Query())
						->distinct()
						->fetchMode(\PDO::FETCH_ASSOC)
						->orderBy(['username' => 'ASC'])
						->limit($limit)
						->select('t.username AS personal, t.email AS address')						
						->search($searchQuery, ['t.username', 't.email']);
		
		if (!empty($foundEmailAddresses)) {
			$query->where(['!=', ['t.email' => $foundEmailAddresses]]);
		}
						
		
		return \GO\Core\Users\Model\User::find($query)->all();		
	}

}
