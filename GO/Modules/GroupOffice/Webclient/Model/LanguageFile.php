<?php

namespace GO\Modules\GroupOffice\Webclient\Model;

class LanguageFile {

	private $file;
	private $data;

	public function __construct($path) {

		$this->file = new \IFW\Fs\File($path);
		
		$this->data = $this->extractVariables();
	}

	private function extractVariables() {
		
		if(!$this->file->exists()) {
			return [];
		}
		
		$data = $this->file->getContents();

		$startpos = strpos($data, '{');
		if($startpos !== false) {
			$startpos = strpos($data, '{', $startpos + 1);

			$endpos = strrpos($data, '}');
			$endpos = strrpos($data, '}', $endpos - strlen($data) - 1);
		}

		if ($startpos && $endpos) {
			$json = $this->fixJSON(substr($data, $startpos, $endpos - $startpos + 1));
			return json_decode($json, true);
		} else {
			return [];
		}
	}

	private function fixJSON($json) {
		$regex = <<<'REGEX'
~
    "[^"\\]*(?:\\.|[^"\\]*)*"
    (*SKIP)(*F)
  | '([^'\\]*(?:\\.|[^'\\]*)*)'
~x
REGEX;

		return preg_replace_callback($regex, function($matches) {
			return '"' . preg_replace('~\\\\.(*SKIP)(*F)|"~', '\\"', $matches[1]) . '"';
		}, $json);
	}

	public function getLanguage() {
		return $this->file->getNameWithoutExtension();
	}
	
	public function __set($name, $value) {
		$this->data[$name] = $value;
	}

	public function __get($name) {
		if (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}

		$trace = debug_backtrace();
		trigger_error(
						'Undefined property via __get(): ' . $name .
						' in ' . $trace[0]['file'] .
						' on line ' . $trace[0]['line'], E_USER_NOTICE);
		return null;
	}

	public function __isset($name) {
		return isset($this->data[$name]);
	}
	
	public function __unset($name) {
		unset($this->data[$name]);
	}
	
	
	public function save() {
		$data = 'angular.module("GO.Core")
		.config(["GO.Core.Providers.TranslateProvider", function (TranslateProvider) {
				TranslateProvider.addTranslations("'.$this->getLanguage().'", 
					'. json_encode($this->data, JSON_PRETTY_PRINT).'
				);
			}]);';
		
		return $this->file->putContents($data);
	}
	
	
	public function getVars() {
		return $this->data;
	}
	

}
