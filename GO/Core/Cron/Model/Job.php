<?php

namespace GO\Core\Cron\Model;

use Cron\CronExpression;
use DateTime;
use Exception;
use GO\Core\Modules\Model\Module;
use GO\Core\Orm\Record;
use IFW\Orm\Query;

/**
 * The Job model
 *
 * @property Module $module
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
	 * @var \IFW\Util\DateTime
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
	
	
	
	/**
	 * Cron job is enabled or not
	 * 
	 * @var boolean 
	 */
	public $enabled;

	protected static function defineRelations() {
		self::hasOne('module', Module::class, ['moduleId'=>'id']);
		
		parent::defineRelations();
	}

	/**
	 * Finds all cronjobs that should be executed.
	 * 
	 * It also finds cron jobs that are not scheduled yet and it will calculate the
	 * scheduled date for the jobs.
	 * 
	 * @return self[]
	 */
	public static function runNext() {
		
		$query = (new Query())
						->where(['enabled'=>1])
						->andWhere(['<=', ['nextRun' => new DateTime()]]);							
		
		$job = self::find($query)->single();

		if($job) {
			$job->run();
		}
	}

	public function internalValidate() {

		if (!CronExpression::isValidExpression($this->cronExpression)) {
			$this->setValidationError('cronExpression', 'INVALIDEXPRESSION');
		}
		
		if(!class_exists($this->cronClassName)) {
			$this->setValidationError('cronClassName', 'CLASSNOTFOUND');
		}
		
		if(!method_exists($this->cronClassName, 'findModuleName')) {
			$this->setValidationError('cronClassName', 'INVALIDCLASS');
		}
		
		if(!method_exists($this->cronClassName, $this->method)) {
			$this->setValidationError('method', 'METHODNOTFOUND');
		}
		
		if($this->isModified('timezone')) {
			try {
				$tz = new \DateTimeZone($this->timezone);
			}catch(\Exception $e) {
				$this->setValidationError('timezone', 'INVALIDTIMEZONE');
			}
		}
		
		if(!$this->getValidationErrors()) {
			$cls = $this->cronClassName;

			$moduleName = $cls::findModuleName();

			if($moduleName) {
	//			var_dump($moduleName);
				$module = \GO\Core\Modules\Model\Module::find(['name' => $moduleName])->single();
				if($module) {
					$this->module = $module;
				}
			}		
		}
		
		if($this->isModified('nextRun') && isset($this->nextRun)) {			
			//round to next minute
			if(!GO()->getDebugger()->enabled) {
				$seconds = 60 - $this->nextRun->format('s');
				$this->nextRun->modify("+$seconds second");
			}
		}
		
		if(($this->isModified('cronExpression') || !isset($this->nextRun)) && $this->enabled) {			
			$this->nextRun = $this->getNextRunDate();
		}
		

		
		if(!$this->enabled) {
			$this->nextRun = null;
		}

		return parent::internalValidate();
	}
	
	private function getNextRunDate() {
		
		//Convert to local time zone stored in job
		$now = new \DateTime();
		$now->setTimezone(new \DateTimeZone($this->timezone));
		$cronExpression = CronExpression::factory($this->cronExpression);
		$localDate = $cronExpression->getNextRunDate($now);
		$localDate->setTimezone(new \DateTimeZone('UTC'));
		
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
		if(!$this->save()) {
			throw new \Exception("Could not save CRON job");
		}
		
		GO()->log("info", "Running CRON method: " . $this->cronClassName . "::" . $this->method, $this);
		
		try {
			$callable = [$this->cronClassName, $this->method];
			if (!is_callable($callable)) {
				trigger_error("CRON method: " . $this->cronClassName . "::" . $this->method . " is not callable!");
			}
			
			GO()->getAuth()->sudo($callable, $this->runUserId);
			
			GO()->log("info", "Finished CRON method: " . $this->cronClassName . "::" . $this->method, $this);
			
		} catch (Exception $ex) {
			
			GO()->error("An exception occurred in CRON method: " . $this->cronClassName . "::" . $this->method . " ".$ex->getMessage(), $this);
			
			GO()->debug((string) $ex);
		}
		
		$this->lastRun = new \DateTime();
		$this->runningSince = null;
		
		$this->nextRun = $this->getNextRunDate();
		
		if(!$this->save()) {
			throw new \Exception("Could not save CRON job");
		}
	}

}
