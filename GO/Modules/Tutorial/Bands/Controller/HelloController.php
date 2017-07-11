<?php

namespace GO\Modules\Tutorial\Bands\Controller;

use GO\Core\Controller;

class HelloController extends Controller {

	public function name($name = "human") {
		$this->render(['data' => 'Hello ' . $name]);
	}

}