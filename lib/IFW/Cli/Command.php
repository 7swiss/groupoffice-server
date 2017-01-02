<?php

namespace IFW\Cli;

use IFW\Data\Object;

class Command extends Object {
	
	private $route;
	
	private $arguments = [];
	
	public function __construct() {
		parent::__construct();
		
		$this->parseArgs();
	}

	private function parseArgs() {
		global $argv;

		//array_shift($argv);
		$count = count($argv);

		if($count > 1){
			$this->route = $argv[1];
		}
				
		if ($count > 2) {
			for ($i = 2; $i < $count; $i++) {
				$arg = $argv[$i];
				if (substr($arg, 0, 2) == '--') {
					$eqPos = strpos($arg, '=');
					if ($eqPos === false) {
						$key = substr($arg, 2);
						$this->arguments[$key] = isset($this->arguments[$key]) ? $this->arguments[$key] : true;
					} else {
						$key = substr($arg, 2, $eqPos - 2);
						$this->arguments[$key] = substr($arg, $eqPos + 1);
					}
				} else if (substr($arg, 0, 1) == '-') {
					if (substr($arg, 2, 1) == '=') {
						$key = substr($arg, 1, 1);
						$this->arguments[$key] = substr($arg, 3);
					} else {
						$chars = str_split(substr($arg, 1));
						foreach ($chars as $char) {
							$key = $char;
							$this->arguments[$key] = isset($this->arguments[$key]) ? $this->arguments[$key] : true;
						}
					}
				} else {
					$this->arguments[] = $arg;
				}
			}
		}
	}

	/**
	 * Get all query parameters of this request
	 * 
	 * @return array ['paramName' => 'value']
	 */
	public function getArguments() {
		return $this->arguments;
	}

	
	/**
	 * Get the route for the router
	 * 
	 * This is the path between cli.php and the query parameters with trailing and leading slashes trimmed.
	 * 
	 * In this example:
	 * 
	 * ./cli.php /some/route --queryParam="value"
	 * 
	 * The route would be "some/route"
	 * 
	 * @param string
	 */
	public function getRoute() {
		return $this->route;
	}
	
	
	/**
	 * Prompt for user input
	 * 
	 * @param string $text
	 * @param string User input
	 */
	public function promptPassword($text){
		
		$command = "/usr/bin/env bash -c 'echo OK'";
		if (rtrim(shell_exec($command)) !== 'OK') {
			throw new \Exception("Can't invoke bash to get prompt");
		}
		$command = "/usr/bin/env bash -c 'read -s";
		
		$command .= " -p";
		
		$command .= " \"";
		
		$command .= $text
						. "\" mypassword && echo \$mypassword'";

		$input =  rtrim(shell_exec($command));
		
		echo "\n";
		
		return $input;
	}

}
