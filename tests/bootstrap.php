<?php

$autoLoader = require(__DIR__."/../vendor/autoload.php");
$autoLoader->add('GO\\', __DIR__);

$app = new \GO\Core\Web\App($autoLoader, require(__DIR__.'/config.php'));

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