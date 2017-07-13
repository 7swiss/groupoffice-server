<?php

namespace GO\Core\Cron\Model;

use Cron\CronExpression;
use DateTimeZone;
use Exception;
use GO\Core\Modules\Model\Module;
use GO\Core\Notifications\Model\Notification;
use GO\Core\Orm\Record;
use GO\Core\Users\Model\User;
use IFW\ErrorHandler;
use IFW\Orm\Query;
use IFW\Util\DateTime;
use IFW\Validate\ErrorCode;
use function GO;

/**
 * The Job model
 *
 * @property Module $module
 * @property User $runUser
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Job extends Record {

	/**
	 * 
	 * @var int
	 */
	public $id;

	/**
	 * 
	 * @var bool
	 */
	public $deleted;

	/**
	 * Set if this cron job belongs to a module and will be deinstalled along with a module.
	 * @var int
	 */
	public $moduleId;

	/**
	 * Name for the job
	 * @var string
	 */
	public $name;

	/**
	 * Class name to call method in
	 * @var string
	 */
	public $cronClassName;

	/**
	 * Method to call
	 * @var string
	 */
	public $method;

	/**
	 * CRON Scheduling expression. See http://en.wikipedia.org/wiki/Cron
	 * 
	 * Remember that all times on the server are in UTC timezone
	 * 
	 * If you set this to null you must set nextRun and it will run once. The job will remove itself when done!
	 * 
	 * @var string
	 */
	public $cronExpression;

	/**
	 * The timezone to calculate run dates in
	 * @var string 
	 */
	public $timezone = 'UTC';

	/**
	 * 
	 * @var int
	 */
	public $runUserId = 1;

	/**
	 * Calculated time this cron will run
	 * @var DateTime
	 */
	public $nextRun;

	/**
	 * Last time this cron ran
	 * @var \DateTime
	 */
	public $lastRun;

	/**
	 * If the cron is running then this is set to the start time.
	 * 
	 * @var \DateTime
	 */
	public $runningSince;
	protected $params;

	/**
	 * Cron job is enabled or not
	 * 
	 * @var boolean 
	 */
	public $enabled;

	/**
	 * Set to the last error message if it occurred
	 * 
	 * @var string 
	 */
	public $lastError;

	protected static function defineRelations() {
		self::hasOne('module', Module::class, ['moduleId' => 'id']);
		self::hasOne('runUser', User::class, ['runUserId' => 'id']);

		parent::defineRelations();
	}

	public function setParams(array $value) {
		$this->params = json_encode($value);
	}

	public function getParams() {
		return isset($this->params) ? json_decode($this->params) : [];
	}

	/**
	 * Finds all cronjobs that should be executed.
	 * 
	 * It also finds cron jobs that are not scheduled yet and it will calculate the
	 * scheduled date for the jobs.
	 * 
	 * @return bool true if a job was ran
	 */
	public static function runNext() {

		$query = (new Query())
						->where(['enabled' => 1])
						->andWhere(['<=', ['nextRun' => new DateTime()]])
						->orderBy(['nextRun' => 'ASC']);

		$job = self::find($query)->single();

		if ($job) {
			$job->run();

			return true;
		} else {
			return false;
		}
	}

	const ERROR_INVALIDCLASS = 10000;
	const ERROR_METHOD_NOT_FOUND = 10001;

	public function internalValidate() {

		if (isset($this->cronExpression) && !CronExpression::isValidExpression($this->cronExpression)) {
			$this->setValidationError('cronExpression', ErrorCode::MALFORMED);
		}

		if (!class_exists($this->cronClassName)) {
			$this->setValidationError('cronClassName', ErrorCode::NOT_FOUND);
		}

		if (!method_exists($this->cronClassName, 'findModuleName')) {
			$this->setValidationError('cronClassName', self::ERROR_INVALIDCLASS, 'Invalid class name');
		}

		if (!method_exists($this->cronClassName, $this->method)) {
			$this->setValidationError('method', self::ERROR_METHOD_NOT_FOUND, 'Class method not found');
		}

		if ($this->isModified('timezone')) {
			try {
				$tz = new DateTimeZone($this->timezone);
			} catch (\Exception $e) {
				$this->setValidationError('timezone', ErrorCode::TIMEZONE_INVALID);
			}
		}

		if (!$this->getValidationErrors()) {
			$cls = $this->cronClassName;

			$moduleName = $cls::findModuleName();

			if ($moduleName) {
				//			var_dump($moduleName);
				$module = Module::find(['name' => $moduleName])->single();
				if ($module) {
					$this->module = $module;
				}
			}
		}

		if ($this->isModified('nextRun') && isset($this->nextRun)) {
			//round to next minute
			if (!GO()->getDebugger()->enabled) {
				$seconds = 60 - $this->nextRun->format('s');
				$this->nextRun->modify("+$seconds second");
			}
		}

		if (($this->isModified('cronExpression') || (!isset($this->nextRun)) && $this->enabled)) {
			$this->nextRun = $this->getNextRunDate();
		}



		if (!$this->enabled) {
			$this->nextRun = null;
		}

		return parent::internalValidate();
	}

	private function getNextRunDate() {

		if (!isset($this->cronExpression)) {
			return null;
		}

		//Convert to local time zone stored in job
		$now = new \DateTime();
		$now->setTimezone(new DateTimeZone($this->timezone));
		$cronExpression = CronExpression::factory($this->cronExpression);
		$localDate = $cronExpression->getNextRunDate($now);
		$localDate->setTimezone(new DateTimeZone('UTC'));

		return $localDate;
	}

	/**
	 * Run the job or schedule it if it has not been scheduled yet.
	 */
	public function run() {
		//Set nextRun to null so it won't run more then once at a time
		$this->nextRun = null;
		//set runningSince to now
		$this->runningSince = new \DateTime();
		$this->lastError = null;

		if (!$this->save()) {
			throw new \Exception("Could not save CRON job");
		}

		GO()->debug("Running CRON method: " . $this->cronClassName . "::" . $this->method);

//		GO()->log("info", "Running CRON method: " . $this->cronClassName . "::" . $this->method, $this);

		try {
			$callable = [$this->cronClassName, $this->method];
			if (!is_callable($callable)) {
				trigger_error("CRON method: " . $this->cronClassName . "::" . $this->method . " is not callable!");
			}

			GO()->getAuth()->sudo($callable, $this->runUser, $this->getParams());
			
			Notification::resume();
			GO()->logResume();

//			GO()->log("info", "Finished CRON method: " . $this->cronClassName . "::" . $this->method, $this);
		} catch (Exception $ex) {
			
			Notification::resume();
			GO()->logResume();
			
			$errorString = ErrorHandler::logException($ex);
			GO()->error($errorString, $this);		

			$this->lastError = $errorString;
		}

		$this->lastRun = new \DateTime();
		$this->runningSince = null;

		$this->nextRun = $this->getNextRunDate();

		if (!isset($this->nextRun)) {
			$this->deleteHard();
		} else if (!$this->save()) {
			throw new \Exception("Could not save CRON job");
		}
	}
	


}
