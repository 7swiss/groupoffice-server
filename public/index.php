<?php
//Include the autoloader that composer has generated.
//We need the classLoader variable so we can find objects in code later.
$classLoader = require(dirname(__DIR__)."/vendor/autoload.php");

use GO\Core\Web\App;

//Create the app with the config.php file
$app = new App($classLoader, require(dirname(__DIR__).'/config.php'));
$app->run();