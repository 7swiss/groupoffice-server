<?php
$root = dirname(dirname(__FILE__));
$cmd = 'find '. escapeshellarg($root).' -type f -name *Controller.php';
exec($cmd, $files, $retVar);

foreach($files as $file) {
	$content = file_get_contents($file);
	
	$content = preg_replace_callback('/public function action(.)/', function($matches) {
		return 'public function '.strtolower($matches[1]);
	}, $content);
	
	file_put_contents($file, $content);
}
