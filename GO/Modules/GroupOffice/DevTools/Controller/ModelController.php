<?php

namespace GO\Modules\GroupOffice\DevTools\Controller;

use GO\Core\Controller;
use IFW\Db\Column;

class ModelController extends Controller {
	
	
	protected function checkAccess() {
		return true;
	}
	
	public function actionList(){
		
		\GO()->getAuth()->sudo(function() {
			$router = new \IFW\Web\Router();

			$classFinder = new \IFW\Util\ClassFinder();
			$models = $classFinder->findByParent(\IFW\Orm\Record::class);

			foreach($models as $model){

				$url = $router->buildUrl('devtools/models/'.urlencode($model).'/props');

				echo '<a href="'.$url.'">'.$model."</a><br />";

			}		
		});
	}

	/**
	 * @param string $modelName
	 */
	public function actionProps($modelName) {

		\GO()->getResponse()->setContentType('text/plain');
		\GO()->getResponse()->send();

		$columns = $modelName::getColumns();

		/* @var  $column Column */
		
		$parts = explode('\\', $modelName);
		
		$modelNameUcfirst = array_pop($parts);

		echo "/**";
		
		echo "\n * The ".$modelNameUcfirst." model\n *";
		foreach ($columns as $name => $column) {

			if ($name == 'ownerUserId') {
				echo "\n * @property int \$ownerUserId";
				echo "\n * @property \GO\Core\Users\Model\User \$owner";
			} else {
				switch ($column->dbType) {
					case 'double':
					case 'float':
						$type = $column->dbType;
						break;
					case 'int':
					case 'tinyint':
					case 'bigint':
						$type = $column->length == 1 ? 'boolean' : 'int';
						break;
					case 'date':
					case 'datetime':
						$type = '\DateTime|string';
						break;
					default:
						$type = 'string';
						break;
				}

				echo "\n * @property " . $type . " \$" . $name.' '.$column->comment;
			}
		}
		
		echo "\n *";
		
		 echo "\n * @copyright (c) ".date('Y').", Intermesh BV http://www.intermesh.nl".
				"\n * @author Merijn Schering <mschering@intermesh.nl>".
				"\n * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3";

		echo "\n */";
		
		echo "\n\nController:\n\n";
		
		$replacements = [
				'modelLowerCase' => strtolower($modelNameUcfirst),
				'modelUcfirst' => $modelNameUcfirst,
				'module' => $parts[2]
				];
		
		$controllerTpl = file_get_contents(dirname(__FILE__).'/../Controller.tpl');
		
		foreach($replacements as $key => $value) {
			$controllerTpl = str_replace('{'.$key.'}', $value, $controllerTpl);
		}
		
		echo $controllerTpl;
	}
	
	
	public function actionTest() {
		
		
//		var_dump(GO()->getAuth()->user()->contact->emailAddresses->all());
		
		$tpl = 'Hi {{user.username}},'
						. '{{#if test.foo}}'."\n"
						. 'Your e-mail {{#if test.bar}} is {{/if}} {{user.email}}'."\n"
						. '{{/if}}'
						. ''
						. '{{#each emailAddress in user.contact.emailAddresses}}'
						. '{{emailAddress.email}} type: {{emailAddress.type}}'."\n"
						. "{{/each}}";
		
		$body = new \IFW\Template\VariableParser();
		$body->addModel('test', ['foo' => 'bar'])
						->addModel('user', GO()->getAuth()->user());
		
		echo $body->parse($tpl);
		
	}
	
	
//	public function actionColumns(){
//		var_dump(\IPE\Modules\Notes\Model\Note::getColumns());
//	}
//
//	public function actionTest() {
//
//		/* @var $finder Finder */
//		
//		$finder = Contact::find(
//						(new Query())
//								->select('t.*, count(emailAddresses.id)')
//								->joinRelation('emailAddresses', false)								
//								->groupBy(array('t.id'))
//								->having("count(emailAddresses.id) > 0")
//						->where(['!=',['lastName' => null]])
//						->andWhere(
//								(new Criteria())
//									->where(['firstName' => ['Merijn', 'Wesley']])
//									->orWhere(['emailAddresses.email'=>'test@intermesh.nl'])
//								)
//
//		);
//		
//		/*
//		 * SELECT t.*, count(emailAddresses.id) FROM `contactsContact` t
//			INNER JOIN `contactsContactEmailAddress` emailAddresses ON (`t`.`id` = `emailAddresses`.`contactId`)
//			WHERE
//			(
//				`t`.`lastName` IS NOT NULL
//			)
//			AND
//			(
//				(
//					`t`.`firstName` IN ("Merijn", "Wesley")
//				)
//				OR
//				(
//					`emailAddresses`.`email` = "test@intermesh.nl"
//				)
//			)
//			AND
//			(
//				`t`.`deleted` != "1"
//			)
//
//			GROUP BY `t`.`id`
//			HAVING
//			(
//				count(emailAddresses.id) > 0
//			)
//		 */
//		
//		
//
//		echo $finder->buildSql();
//		
//		
////		var_dump($finder->aliasMap);
//		
//		$contacts = $finder->all();
////		var_dump($finder->bindParameters);
//		
//		var_dump($contacts);
//		
//		var_dump(GO()->debugger()->entries);
//	}
//	
//	
//	public function actionImap(){
//		
//		$account = Account::find()->single();
//		
//		$account->sync();
//		
//	}
		

}
