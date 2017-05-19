<?php
return [
	'GO\Core\Auth\Model\Token' => [
		 "checkXSRFToken" => false
	],
	'IFW\Config' => [
			'productName' => 'Group-Office 7.0',
			'dataFolder'=> '/tmp/groupoffice/phpunit/data',
			'cacheClass' => "\\IFW\\Cache\\None" //set to none for development
	],
	'IFW\Db\Connection' => [
			'user' => 'root',
			'port' => 3306,
			'pass' => '',
			'database' => 'go7_test',
			'host' => 'localhost',
	],
	'IFW\Validate\ValidatePassword' => [
		'enabled' => true
	],
];
