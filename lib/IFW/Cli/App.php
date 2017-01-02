<?php
namespace IFW\Cli;

use IFW\App as BaseApp;
use IFW\Cli\Router;

abstract class App extends BaseApp {
	
	private $command;
	
	public function getCommand() {
		if(!$this->command) {
			$this->command = new Command();
		}
		
		return $this->command;
	}
	
	public function getRouter() {
		if(!isset($this->router)) {
			$this->router = new Router();
		}		
		return $this->router;
	}
}