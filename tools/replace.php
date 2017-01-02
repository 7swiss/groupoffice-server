#!/usr/bin/php
<?php

//When another file includes this file we need to make sure we find the relative paths
chdir(dirname(__FILE__));

//Include the autoloader that composer has generated.
//We need the classLoader variable so we can find objects in code later.
require("../lib/IFW/IFW.php");

$app = new GO\Core\Cli\App(require('../config.php'));

$classFinder = new IFW\Util\ClassFinder();
$records = $classFinder->findByParent(IFW\Orm\Record::class);
$records = array_reverse($records);


//exec("find ../GO -name *.sql", $files);
//
//
//foreach($files as $fileName) {
//
//
//	$file = new \IFW\Fs\File(realpath($fileName));
//	$sql = $file->getContents();
//	
//
//	foreach($records as $record) {
//		/* @var $record IFW\Orm\Record */
//		$sql = str_replace($record::tableName(), \IFW\Util\String::camelCaseToUnderscore($record::tableName()), $sql);
//	}
//
//	$file->putContents($sql);
//}


$file = new \IFW\Fs\File('/home/mschering/Downloads/go7.sql');

$sql = $file->getContents();



foreach($records as $record) {
	/* @var $record IFW\Orm\Record */
	$sql = str_replace($record::tableName(), \IFW\Util\StringUtil::camelCaseToUnderscore($record::tableName()), $sql);
}

echo $sql;

$file->putContents($sql);

