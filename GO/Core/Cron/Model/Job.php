<?php

namespace GO\Core\Cron\Model;

use Cron\CronExpression;
use DateTime;
use Exception;
use GO\Core\Modules\Model\Module;
use GO\Core\Orm\Record;
use IFW\Orm\Query;
use function GO;

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
	 * @var string
	 */							
	public $cronExpression;

	/**
	 * 
	 * @var int
	 */							
	public $runUserId = 1;

	/**
	 * Calculated time this cron will run
	 * @var \DateTime
	 */							
	public $nextRun;

	/**
	 * Last time this cron ran
	 * @var \DateTime
	 */							
	public $lastRun;

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
						->where(['<=', ['nextRun' => new DateTime()]])
						->orWhere(['nextRun' => null]);
		
		$job = self::find($query)->single();

		if($job) {
			$job->run();
		}
	}

	public function internalValidate() {

		if (!CronExpression::isValidExpression($this->cronExpression)) {
			$this->setValidationError('cronExpression', 'invalid CRON expression');
		}

		return parent::internalValidate();
	}

	/**
	 * Run the job or schedule it if it has not been scheduled yet.
	 */
	public function run() {

		if (!isset($this->nextRun)) {
			$cronExpression = CronExpression::factory($this->cronExpression);
			$this->nextRun = $cronExpression->getNextRunDate();
			$this->save();
		} else {
			$cronExpression = CronExpression::factory($this->cronExpression);
			$this->nextRun = $cronExpression->getNextRunDate();
			$this->save();			

			$this->runMethod();
		}
	}

	private function runMethod() {
		
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
		}
		
		$this->lastRun = new \DateTime();
		$this->save();
	}

}
