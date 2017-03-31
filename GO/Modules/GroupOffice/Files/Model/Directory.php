<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Files\Model;

/**
 * Directory
 */
class Directory extends Node {

	const RootID = 1;

	protected function init() {
		$this->isDirectory = true;
		parent::init();
	}

	public static function tableName() {
		return 'files_node';
	}

	public function getName() {
		return (Drive::home()->rootId == $this->id) ? 'Home' : $this->name;
	}

}