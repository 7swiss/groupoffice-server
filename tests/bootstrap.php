<?php

$autoLoader = require(__DIR__."/../vendor/autoload.php");
$autoLoader->add('GO\\', __DIR__);

$app = new \GO\Core\Web\App($autoLoader, require(__DIR__.'/config.php'));

$controller = new \GO\Core\Install\Controller\SystemController();
$response = $controller->actionInstall();
