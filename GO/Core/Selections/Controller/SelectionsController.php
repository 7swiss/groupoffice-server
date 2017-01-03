<?php
namespace GO\Core\Selections\Controller;

use GO\Core\Controller;

class SelectionsController extends Controller {
	public function actionCreate() {
		
		switch(strtolower(GO()->getRequest()->getBody()['method'])) {
			case 'delete':
				$this->delete(GO()->getRequest()->getBody()['data']);
				break;
			
			case 'undelete':
				$this->undelete(GO()->getRequest()->getBody()['data']);
				break;
			
			
			case 'update':
				$this->update(GO()->getRequest()->getBody()['data']);
				break;		
			
			default:
				throw new \Exception("method not defined");
		}
	}
	
	private function delete($data) {		
		foreach($data as $record) {
			$cls = $record['className'];
			$r = $cls::findByPk($record['pk']);			
			$r->delete();
			$this->renderModel($r, implode(',',array_keys($record['pk'])).',deleted');
		}
	}
	
	private function undelete($data) {		
		foreach($data as $record) {
			$cls = $record['className'];
			$r = $cls::findByPk($record['pk']);			
			$r->deleted = false;
			$r->save();
			$this->renderModel($r, implode(',',array_keys($record['pk'])).',deleted');
		}
	}
	
	private function update($data) {		
		foreach($data as $record) {
			$cls = $record['className'];
			$r = $cls::findByPk($record['pk']);			
			$r->setValues($record['data']);		
			
			$this->renderModel($r);
		}
	}
}
