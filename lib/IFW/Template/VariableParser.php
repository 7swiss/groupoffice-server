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
		$this->filters['date'] = function(\IFW\Util\DateTime $date = null, $format = "d-m-Y") {
			
			if(!isset($date)) {
				return "";
			}
			
			if(\IFW::app()->getAuth()->user()) {
				$date->setTimezone(\IFW::app()->getAuth()->user()->getTimezone());
			}else
			{
				$date->setTimezone(new \DateTimeZone("europe/amsterdam"));
			}
			
			return $date->format($format);
		};
		
		$this->filters['number'] = function($number,$decimals=2, $decimalSeparator='.', $thousandsSeparator=',') {
			return number_format($number,$decimals, $decimalSeparator, $thousandsSeparator);
		};
		
		$this->models['now'] = new \IFW\Util\DateTime();
	}
	
	
	private function findBlocks($str) {
			
		preg_match_all('/\n?{{#(each|if)([^}]+)}}\n?/s', $str, $openMatches, PREG_OFFSET_CAPTURE|PREG_SET_ORDER);
		preg_match_all('/{{\/(each|if)}}\n?/s', $str, $closeMatches, PREG_OFFSET_CAPTURE|PREG_SET_ORDER);
		
		$count = count($openMatches);
		if($count != count($closeMatches)) {
			
//			var_dump($str);
			
			throw new \Exception("Open and close tags don't match");
		}
		
//		var_dump($closeMatches);
		
		$tags = [];
		
		
		for($i = 0; $i < $count; $i++) {			
			$offset = $openMatches[$i][0][1];
			$tags[$offset] = ['tagName' => $openMatches[$i][1][0], 'type' => 'open', 'offset' => $offset, 'expression' => trim($openMatches[$i][2][0]), 'tagLength' => strlen($openMatches[$i][0][0])];			
		}
		
		
		for($i = 0; $i < $count; $i++) {			
			$offset = $closeMatches[$i][0][1];
			$tags[$offset] = ['tagName' => $closeMatches[$i][1][0], 'type' => 'close', 'offset' => $offset, 'tagLength' => strlen($closeMatches[$i][0][0])];			
		}
		
		//close and open 
		ksort($tags);
		$tags = array_values($tags);		
		
		
		$tags = $this->findCloseTags($tags);
				
		
		$tags = array_values(array_filter($tags, function($tag) {
			return $tag['type'] == 'open' && isset($tag['close']);
		}));
		
		for($i = 0, $c = count($tags); $i < $c; $i++) {
			$tags[$i]['tpl'] = substr($str, $tags[$i]['offset'] + $tags[$i]['tagLength'], $tags[$i]['close']['offset'] - $tags[$i]['offset'] - $tags[$i]['tagLength']); 
		}
		return $tags;
	}
	
	private function findCloseTags($tags) {
		
		$open = 0;
		$current = null;
		$tagName = null;
		
		for($i = 0, $count = count($tags); $i < $count; $i++) {	
			
			if($open > 0 && $tags[$i]['tagName'] != $tagName) {
				continue;
			}
			
			if($tags[$i]['type'] == 'open') {
				$open++;
				if($open == 1) {
					$tagName = $tags[$i]['tagName'];
					$current = $i;
				}												
			} else {
				$open--;				
				if($open == 0) {
					$tags[$current]['close'] = $tags[$i];					
				}
			}
		}
		
		return $tags;
	}
/*
<br>&nbsp; - {{license.course.name}} {{#if license.expiresAt != null}}verloopt op {{license.expiresAt | date}}{{/if}}{{#if !license.expiresAt}}Nog niet behaald{{/if}}&nbsp;<br>
  */
	public function parse($str) {		
		
//		echo "\n--- Input:\n";
//		var_dump($str);
		
		$tags = $this->findBlocks($str);
		
		
		for($i = 0;$i < count($tags); $i++) {
			if($tags[$i]['tagName'] == 'if') {
				$tags[$i] = $this->replaceIf($tags[$i], $str);
			} else
			{
				$tags[$i] = $this->replaceEach($tags[$i], $str);
			}
		}
		
//		var_dump($tags);
		$replaced = "";
		$offset = 0;
		foreach($tags as $tag) {
			
//			var_dump($tag);
			
			if($tag['offset'] > 0) {
				$cut = substr($str, $offset, $tag['offset'] - $offset);
//				echo "\n--- Cut:\n";
//				var_dump($cut);
//				var_dump($offset);
//				var_dump($tag['offset']);
				$replaced .= $cut;
			}
			
//			echo "\n--- Replace:\n";
//			var_dump(substr($str, $tag['offset'], $tag['close']['offset'] + $tag['close']['tagLength'] - $tag['offset']));
//			echo "\n--- With:\n";
//			var_dump($tag['replacement']);
//			var_dump('------');
//			
			$replaced .=  $tag['replacement'];
			$offset = $tag['close']['offset'] + $tag['close']['tagLength'];
		}
		
		$replaced .= substr($str, $offset);
				
		$replaced = preg_replace_callback('/{{[^}]*}}/', [$this, 'replaceVar'], $replaced);
		
//		echo "\n--- Result:\n";
//		var_dump($replaced);
		
		return $replaced;
	}
	
	
	private function replaceEach($tag, $str) {
		
		
		//example emailAddress in contact.emailAddresses
		$expressionParts = array_map('trim', explode(' in ', $tag['expression']));
//		var_dump($expressionParts);
		$array = $this->getVar(trim($expressionParts[1]));	
		
		if(!is_array($array) && !($array instanceof \Traversable)) {
			$tag['replacement'] = "";
			return $tag;
//			throw new \Exception('Invalid '.$matches[1].' '.$expressionParts[1].' = '.var_export($array, true));
		}
		
		$varName = trim($expressionParts[0]);
		
		
		
		$replacement = '';
		foreach($array as $model) {
			
			$this->addModel($varName, $model);		
			
			$add = $this->parse($tag['tpl']);
			$replacement .= $add;
		}
		
		$tag['replacement'] = $replacement;
		
		return $tag;
//		return substr($str, 0, $block['offset']) . $replacement . substr($str, $block['close']['offset'] + $block['close']['tagLength']);
		
	}

	private static $tokens = ['==','!=','>','<', '(', ')', '&&', '||', '*', '/', '%', '-', '+'];

	private function replaceIf($tag, $str) {
		
		$expression = $this->validateExpression($tag['expression']);
		
		$ret = eval($expression);	
		if($ret){
			$tag['replacement'] = $this->parse($tag['tpl']);
		}else
		{
			$tag['replacement'] = '';
		}
		
		return $tag;

		
//		return substr($str, 0, $block['offset']) . $replacement . substr($str, $block['close']['offset'] + $block['close']['tagLength']);
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
							$part == 'null' ||
							in_array($part, self::$tokens) ||
							$this->isString($part)											
				) {
				$str .= $part.' ';
			}elseif($this->isVar(ltrim($part, "!")))
			{
				if($part[0] == "!") {
					$str .= "!";
				}
				$str .= var_export($this->getVar(ltrim($part, "!")), true).' ';
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
			
			$args = array_map('trim', str_getcsv($filter, ':', "'"));
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
			if(is_array($model)) {
				if(!array_key_exists($pathPart, $model)) {
					return false;
				}

				$model = $model[$pathPart];
			}else
			{
				if(!$model->hasReadableProperty($pathPart)) {
					return false;
				}

				$model = $model->$pathPart;
			}
			
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
