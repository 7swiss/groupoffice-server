<?php

$autoLoader = require(__DIR__."/../vendor/autoload.php");
$autoLoader->add('GO\\', __DIR__);


$app = new \GO\Core\Web\App($autoLoader, require(__DIR__.'/config.php'));

$old = umask(0); //world readable
$app->getConfig()->getDataFolder()->delete();
$app->getConfig()->getDataFolder()->create();
umask($old);


//cleanup previous database
GO()->getDbConnection()->query("DROP DATABASE IF EXISTS `" . $app->getDbConnection()->database ."`");
GO()->getDbConnection()->query("CREATE DATABASE `" . $app->getDbConnection()->database ."`");
GO()->getDbConnection()->disconnect();
$app->reinit();

$controller = new \GO\Core\Install\Controller\SystemController();
$response = $controller->actionInstall();

// create 2 test users (admin already exists
// Use \Util\UserTrait to switch between the, in test cases
$henk = new \GO\Core\Users\Model\User();
$henk->username = 'henk';
$henk->email = 'henk@phpunit.dev';
$henk->save();

$piet = new \GO\Core\Users\Model\User();
$piet->username = 'piet';
$piet->email = 'piet@phpunit.dev';
$piet->save();