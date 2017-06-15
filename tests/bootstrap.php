<?php

use GO\Core\Install\Controller\SystemController;
use GO\Core\Users\Model\Group;
use GO\Core\Users\Model\User;
use GO\Core\Web\App;

$autoLoader = require(__DIR__."/../vendor/autoload.php");
$autoLoader->add('GO\\', __DIR__);


$app = new App($autoLoader, require(__DIR__.'/config.php'));

$old = umask(0); //world readable
$app->getConfig()->getDataFolder()->delete();
$app->getConfig()->getDataFolder()->create();
umask($old);


//cleanup previous database
GO()->getDbConnection()->query("DROP DATABASE IF EXISTS `" . $app->getDbConnection()->database ."`");
GO()->getDbConnection()->query("CREATE DATABASE `" . $app->getDbConnection()->database ."`");
GO()->getDbConnection()->disconnect();
$app->reinit();

$controller = new SystemController();
$response = $controller->actionInstall();


$internalGroup = Group::findInternalGroup();

// create 2 test users (admin already exists
// Use \Util\UserTrait to switch between the, in test cases
$henk = new User();
$henk->username = 'henk';
$henk->email = 'henk@phpunit.dev';
$henk->groups[] = $internalGroup;
$henk->save();

$piet = new User();
$piet->username = 'piet';
$piet->email = 'piet@phpunit.dev';
$piet->groups[] = $internalGroup;
$piet->save();