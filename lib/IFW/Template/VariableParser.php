<?php

namespace IFW\Template;

use stdClass;

/**
 * Template parser
 * 
 * @example
 * 
 * ``````````````````````````````````````````````````````````````````````
 * 
 * $body = new \IFW\Template\VariableParser();
 * $body->addModel('day', date('l'))
 * 			->addModel('user', GO()->getAuth()->user());
 * 		
 * echo $body->parse("Hello {{user.username}}, It's {{day}} today");
 * 
 * ````````````````````````````````````````````````````````````````````````
 * 
 * More complex example with if and each
 * 
 * `````````````````````````````````````````````````````````````````````````````
 * $tpl = 'Hi {{user.username}},'
 *						. '{{#if test.foo}}'."\n"
 *						. 'Your e-mail {{#if test.bar}} is {{/if}} {{user.email}}'."\n"
 *						. '{{/if}}'
 *						. ''
 *						. '{{#each emailAddress in user.contact.emailAddresses}}'
 *						. '{{emailAddress.email}} type: {{emailAddress.type}}'."\n"
 *						. "{{/each}}";
 *		
 *		$tplParser = new \IFW\Template\VariableParser();
 *		$tplParser->addModel('test', ['foo' => 'bar'])
 *						->addModel('user', GO()->getAuth()->user());
 *		
 *		echo $tplParser->parse($tpl);
 *``````````````````````````````````````````````````````````````````````````````		
 * 
 * 
 * @exanple More complex if statement
 * `````````````````````````````````````````````````````````````````````````````
 * {{debtor.contact.name}}
 * {{#each address in debtor.contact.addresses}}
 * {{#if address.type=="billing"}}
 * {{address.formatted}}
 * {{/if}}
 * {{/each}}
 * ````````````````````````````````````````````````````````````````````````````
 * 
 * Outputs: Hello admin, It's Tuesday today.
 */
class VariableParser {

	private $models = [];
	
	public function __construct() {
		$this->filters['date'] = function(\IFW\Util\DateTime $date = null, $format = null) {
			return isset($date) ? $date->toLocaleFormat() : "";
		};
		
		$this->filters['number'] = function($number,$decimals=2, $decimalSeparator='.', $thousandsSeparator=',') {
			return number_format($number,$decimals, $decimalSeparator, $thousandsSeparator);
		};
		
		$this->models['now'] = new \IFW\Util\DateTime();
	}


	public function parse($str) {		
		
		$str = preg_replace_callback('/\n?{{#each([^}]+)}}(.*){{\/each}}\n?/s', [$this, 'replaceEach'], $str);		
		
		$str = preg_replace_callback('/\n?{{#if([^}]+)}}(.*){{\/if}}\n?/s', [$this, 'replaceIf'], $str);		
		
		$str = preg_replace_callback('/{{[^}]*}}/', [$this, 'replaceVar'], $str);
		
		return $str;
	}
	
	
	private function replaceEach($matches) {
		
		$expression = trim($matches[1]);
		
		//example emailAddress in contact.emailAddresses
		$expressionParts = array_map('trim', explode(' in ', $expression));
//		var_dump($expressionParts);
		$array = $this->getVar(trim($expressionParts[1]));	
		
		if(!is_array($array) && !($array instanceof \Traversable)) {
			return '';
//			throw new \Exception('Invalid '.$matches[1].' '.$expressionParts[1].' = '.var_export($array, true));
		}
		
		$varName = trim($expressionParts[0]);
		
		$tpl = $matches[2];
		
		$str = '';
		foreach($array as $model) {
			
			$this->addModel($varName, $model);		
			$str .= $this->parse($tpl);
		}
		
		return $str;
	}

	private static $tokens = ['==','!=','>','<', '(', ')', '&&', '||'];

	private function replaceIf($matches) {
		$expression = trim($matches[1]);
		
		$expression = $this->validateExpression($expression);
		
		$ret = eval($expression);
		
		if($ret){
			return $this->parse($matches[2]);
		}else
		{
			return '';
		}
	}
	
	private function validateExpression($expression) {
		//split string into tokens. See http://stackoverflow.com/questions/5475312/explode-string-into-tokens-keeping-quoted-substr-intact
		
		foreach(self::$tokens as $token) {			
			$expression = str_replace($token, ' '.$token.' ', $expression);
		}
		$expression = str_replace(';', ' ; ', $expression);
		
		$parts = preg_split('#\s*((?<!\\\\)"[^"]*")\s*|\s+#', $expression, -1 , PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		
		
		$parts = array_map('trim', $parts);
		
		$str = '';
		
		foreach($parts as $part) {
			
			if($part == ';') {
				throw new \Exception('; not allowed in expressions');
			}
			
			if(
							empty($part) ||
							is_numeric($part) ||
							$part == 'true' ||
							$part == 'false' ||
							in_array($part, self::$tokens) ||
							$this->isString($part)											
				) {
				$str .= $part.' ';
			}elseif($this->isVar($part))
			{
				$str .= var_export($this->getVar($part), true).' ';
			} else
			{			
				throw new \Exception("Invalid token: ".var_export($part, true));
			}
			
		}
//		echo $str;
//		exit();
//		throw new \Exception($str);
		
		return 'return ('.$str.');';
	}
	
	private function isString ($str) {
		return preg_match('/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s', $str);
	}

	private function replaceVar($matches) {		
		
		$str = substr($matches[0], 2, -2);
		
		$filters = explode('|', $str);
		
		$varPath = trim(array_shift($filters)); //eg "contact.name";		
		
		$value = $this->getVar($varPath);
		
		foreach($filters as $filter) {
			
			$args = array_map('trim', explode(':', $filter));				
			$filterName = array_shift($args);
			array_unshift($args, $value);
			
			$value = call_user_func_array($this->filters[$filterName], $args);
		}
		
		return $value;
	}
	
	private $filters = [];
	
	
	private function isVar($path) {
		$pathParts = explode(".", trim($path)); //eg "contact.name"		

		$model = $this;

		foreach ($pathParts as $pathPart) {
			if(!$model->hasReadableProperty($pathPart)) {
				return false;
			}
			$model = $model->$pathPart;
		}

		return true;
	}
	
	private function getVar($path) {
		
//		echo 'getVar('.trim($path).')';
		$pathParts = explode(".", trim($path)); //eg "contact.name"		

		$model = $this;

		foreach ($pathParts as $pathPart) {
			if(is_array($model)) {
				if (!isset($model[$pathPart])) {
					return null;
				}
				$model = $model[$pathPart];
			}else
			{
				if (!isset($model->$pathPart)) {
					return null;
				}
				$model = $model->$pathPart;
			}
			
		}

		return $model;
	}
	
	
	public function hasReadableProperty($name) {
		return array_key_exists($name, $this->models);
	}

	/**
	 * Add a key value array or object to add for the parser.
	 * 
	 * @param string $name
	 * @param array|stdClass $model 
	 * @return TemplateParser
	 */
	public function addModel($name, $model) {
		$this->models[$name] = $model;

		return $this;
	}

	public function __isset($name) {
		return isset($this->models[$name]);
	}

	public function __get($name) {
		return $this->models[$name];
	}

}
