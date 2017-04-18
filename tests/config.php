<?php
return [
	'GO\Core\Auth\Model\Token' => [
		 "checkXSRFToken" => false
	],
	'IFW\Config' => [
			'productName' => 'Group-Office 7.0',
			'dataFolder'=>dirname(__FILE__).'/data',
			'cacheClass' => "\\IFW\\Cache\\None" //set to none for development
	],
	'IFW\Db\Connection' => [
			'user' => 'admin',
			'port' => 3306,
			'pass' => 'mks14785',
			'database' => 'go7_test',
			'host' => 'localhost',
	],
	'IFW\Validate\ValidatePassword' => [
		'enabled' => true
	]
];
