<?php
return [
	'GO\Core\Auth\Browser\Model\Token' => [
		 "checkXSRFToken" => false
	],
	'IFW\Config' => [
			'productName' => 'Group-Office 7.0',
			'dataFolder'=>dirname(__FILE__).'/data',
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
	]
];
