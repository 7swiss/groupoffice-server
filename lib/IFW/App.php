<?php
namespace IFW;

use Composer\Autoload\ClassLoader;
use GO\Core\Install\Model\System;
use IFW;
use IFW\Auth\Permissions\Model;
use IFW\Auth\UserProviderInterface;
use IFW\Cache\CacheInterface;
use IFW\Cache\Disk;
use IFW\Cache\None;
use IFW\Db\Connection;
use IFW\Event\StaticListeners;
use IFW\Modules\ModuleCollection;
use IFW\Orm\Record;
use IFW\Process\Model\Process;
use IFW\Util\Environment;
use IFW\Util\StringUtil;
use IFW\Web\Router;

/**
 * App singleton class with services
 * 
 * The App class is a collection of static functions to access common services
 * like the configuration, request, debugger etc.
 * 
 * <p>Example:</p>
 * ```````````````````````````````````````````````````````````````````````````
 * $this->config()->getTempFolder();
 * ```````````````````````````````````````````````````````````````````````````
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class App {

	/**
	 *
	 * @var CacheInterface 
	 */
	protected $cache;
	
	/**
	 *
	 * @var ErrorHandler
	 */
	protected $errorHandler;
	
	/**
	 *
	 * @var Connection 
	 */
	protected $dbConnection;
	
	/**
	 *
	 * @var Debugger 
	 */
	protected $debugger;
	
	
	/**
	 *
	 * @var Environment 
	 */
	protected $environment;
	
	/**
	 *
	 * @var type 
	 */
	protected $router;
	
	
	/**
	 *
	 * @var ModuleCollection 
	 */
	protected $moduleCollection;
	
	/**
	 *
	 * @var Config 
	 */
	private $config;
	
	
	/**
	 *
	 * @var Process 
	 */
	protected $process;
	
	/**
	 *
	 * @var ClassLoader 
	 */
	private $classLoader;

	/**
	 * Initializes the framework.
	 * 
	 * Set's custom error handling and configures the framework.
	 * 
	 * @param array $config Config object with properties per class name.
	 * 
	 * <p>Example:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * array(
	 * 			
	 * 			'IFW\Config'=>array(
	 * 				'productName'=>'GO Application'	
	 * 			),
	 * 			
	 * 			'IFW\Debugger'=>array(
	 * 				'enabled'=>true	
	 * 			),
	 * 			
	 * 			'IFW\Db\Connection'=>array(
	 * 					'user'=>'root',
	 * 					'port'=>3306,
	 * 					'pass'=>'',
	 * 					'database'=>'intermesh',
	 * 					'host'=>'localhost',
	 * 			),
	 * 			
	 * 	))
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 */
	public function __construct($classLoader, array $config) {
		
		IFW::setApp($this);

		$this->config = new Config($config);
		date_default_timezone_set("UTC");
				
		$this->errorHandler = new ErrorHandler();		
		
		//Get the class loader from composer. Couldn't find a better way to do this.
		$this->classLoader = $classLoader;
					
		$this->getDebugger()->setSection(Debugger::SECTION_INIT);		

		$this->init();
		
		$this->debug("IFW Core Initialized");	
	}

	/**
	 * Reinialize the application
	 *
	 * It will rescan record relations, listeners and router routes.
	 */
	public function reinit() {
		GO()->getCache()->flush(false);
		Model::$enablePermissions = false;
		unset($this->moduleCollection);
		$this->init();
	}
	
	/**
	 * Called at the end of the constructor
	 * 
	 * Override this to initialize your app.
	 */
	protected function init() {

		Record::initRelations();
		StaticListeners::singleton()->initListeners();

		$this->getRouter()->initRoutes();

		Model::$enablePermissions = true;
	}
	
	/**
	 * The composer class loader
	 *
	 * @return ClassLoader	 
	 */
	public function getClassLoader() {
		return $this->classLoader;
	}
	
	
	/**
	 * 
	 * @return Process
	 */
	public function getProcess() {
		if(!isset($this->process)) {
			$this->process = new Process();
		}
		
		return $this->process;
	}
	
	/**
	 * 
	 * @return UserProviderInterface
	 */	
	abstract public function getAuth();
	
	/**
	 * Get the module collection
	 * 
	 * It's an array of class names. eg.:
	 * 
	 * ```````````````````````````````````````````````````````````
	 * ["GO\Modules\Contacts\Module","GO\Modules\Tasks\Module"]
	 * ```````````````````````````````````````````````````````````
	 * 
	 * @param string[]
	 */
	public function getModules() {
		if(!isset($this->moduleCollection)) {
			$this->moduleCollection = new ModuleCollection();
		}		
		return $this->moduleCollection;
	}	
		
	/**
	 * Get the application router
	 * @return Router
	 */
	abstract public function getRouter();
	
	/**
	 * Get the Group-Office configuration
	 * 
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Get a simple key value caching object
	 * 
	 * @return Disk
	 */
	public function getCache() {
		if (!isset($this->cache)) {			
			$cls = $this->getConfig()->cacheClass;
			$this->cache = new $cls;
			if(!$this->cache->isSupported()) {
				$this->cache = new None();
			}
		}
		return $this->cache;
	}

	/**
	 * Get the database connection
	 * 
	 * @return Connection
	 */
	public function getDbConnection() {
		if (!isset($this->dbConnection)) {
			$this->dbConnection = new Connection();
		}

		return $this->dbConnection;
	}
	
	/**
	 * Get the server environment object
	 * 
	 * @return Environment
	 */
	public function getEnvironment() {
		if (!isset($this->environment)) {
			$this->environment = new Environment();
		}

		return $this->environment;
	}

	/**
	 * Get a simple key value caching object
	 * 
	 * @return Debugger
	 */
	public function getDebugger() {
		if (!isset($this->debugger)) {
			$this->debugger = new Debugger();
		}

		return $this->debugger;
	}

	/**
	 * Add debug output
	 * 
	 * {@see Debugger::debug()}
	 * 
	 * @param string|callable|array|object $msg
	 */
	public function debug($msg, $type = 'general', $traceBackSteps = 0) {				
		$this->getDebugger()->debug($msg, $type, $traceBackSteps);		
	}
	
	/**
	 * Run the application
	 */
	public function run() {
		$this->getRouter()->run();
	}
}
