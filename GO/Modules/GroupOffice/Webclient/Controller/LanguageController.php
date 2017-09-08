<?php

namespace GO\Modules\GroupOffice\Webclient\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Webclient\Model\LanguageFile;

class LanguageController extends Controller {
	
	
	const LANGUAGES = ['nl','de','fr','gr','hu','pl','ro'];

	private $lang;
	private $webclientRoot;

	/**
	 * /var/www/groupoffice-server/bin/groupoffice webclient/language/update-all --lang=nl --root=./app

	 * @param type $lang
	 * @param type $root
	 */
	public function updateAll($root) {
		
		foreach(self::LANGUAGES as $lang) {
			$this->lang = $lang;

			$this->webclientRoot = realpath($root);

			$this->createMissing();


			$langFiles = [];

			$cmd = 'find '. escapeshellarg($root).' -type f -name ' . $this->lang . '.js';
			exec($cmd, $langFiles, $retVar);

			foreach ($langFiles as $langFile) {

				echo "Updating " . $langFile . "\n\n";

				$this->updateFile($langFile);
			}
		}
	}

	private function createMissing() {
		$cmd = 'find . -type d -name language';
		exec($cmd, $langDirs, $return_var);

		//create non existing lang files
		foreach ($langDirs as $langDir) {

			$langFile = $langDir . '/' . $this->lang . '.js';

			touch($langFile);
		}
	}
	
	
	private function findHtmlFiles($path) {
		
		$cmd = 'find '. escapeshellarg($path).' -type f \( -iname "*.html" \);';
		exec($cmd, $scripts, $return_var);
		
		return $scripts;
	}
	
	private function findJsFiles($path) {
		$cmd = 'find '. escapeshellarg($path).' -type f \( -iname "*.js" \);';
		exec($cmd, $scripts, $return_var);
		
		return $scripts;
	}
	
	
	private function getHtmlLanguageVars($file) {
			$content = file_get_contents($file);

			preg_match_all('/\{(::)?"([^"]+)"[\s]*\|[\s]*goT[^\}]*\}/', $content, $matches);
			$keys = $matches[2];


			preg_match_all("/\{(::)?'([^']+)'[\s]*\|[\s]*goT[^\}]*\}/", $content, $matches);
			$keys = array_merge($keys, $matches[2]);


			preg_match_all('/<go-multiple.*title="([^"]*)"/', $content, $matches);
			$keys = array_merge($keys, $matches[1]);

			preg_match_all('/<go-multiple.*title=\'([^\']*)\'/', $content, $matches);
			$keys = array_merge($keys, $matches[1]);


			preg_match_all('/<go-.*label="([^"]*)"/', $content, $matches);
			$keys = array_merge($keys, $matches[1]);

			preg_match_all('/<go-.*label=\'([^\']*)\'/', $content, $matches);
			$keys = array_merge($keys, $matches[1]);
			
			return $keys;
	}
	
	private function getJsLanguageVars($file) {
		$content = file_get_contents($file);

			preg_match_all('/Translate\.t\s*\(\s*[\'"](.+?)[\'"]\s*[\),]/', $content, $matches);

			$keys = $matches[1];
			
			preg_match_all('/App.addLauncher\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
			$keys = array_merge($keys, $matches[1]);
			
			return array_map('stripslashes', $keys);
	}
	
	public function updateFile($langFile) {

//		chdir($this->webclientRoot.'/app/'.dirname(dirname($langFile)));

		$coreLangFile = new LanguageFile($this->webclientRoot . "/core/language/" . basename($langFile));

		
		$moduleLangFile = new LanguageFile($langFile);

		$modulePath = dirname(dirname($langFile));
		$files = $this->findHtmlFiles($modulePath);

		

		foreach ($files as $file) {		
			$keys = $this->getHtmlLanguageVars($file);
			foreach ($keys as $str) {
				if (!isset($moduleLangFile->{$str})  && !isset($coreLangFile->{$str})) {
					$moduleLangFile->{$str} = $str;
				}
			}
		}
		

		$files = $this->findJsFiles($modulePath);

		foreach ($files as $file) {
			$keys = $this->getJsLanguageVars($file);			

			foreach ($keys as $str) {
				if (!isset($moduleLangFile->{$str})  && !isset($coreLangFile->{$str})) {
					$moduleLangFile->{$str} = $str;
				}
			}
		}
		
		$moduleLangFile->save();
	}
	
	private $delimiter = ';';
	
	/**
	 * /var/www/groupoffice-server/bin/groupoffice webclient/language/export-csv -h=localhost --root=./app --output=/tmp/languages.csv

	 * @param type $lang
	 * @param type $root
	 * @param type $output
	 */
	public function exportCsv($root, $output) {
		
		$this->updateAll($root);
		
		$fp = fopen($output, 'w+');

		
		foreach (self::LANGUAGES as $lang) {
			$cmd = 'find '. escapeshellarg($root).' -type f -name '.$lang.'.js';		
			exec($cmd, $langFiles, $return_var);


			foreach($langFiles as $langFilePath) {	

				$langFile = new LanguageFile($langFilePath);

				foreach($langFile->getVars() as $key => $translation) {
					fputcsv($fp,[$key, $translation, str_replace($root, '', $langFilePath)], $this->delimiter);
				}
			}
		}
		fclose($fp);
	}
	
	/**
	 * /var/www/groupoffice-server/bin/groupoffice webclient/language/import-csv  -h=localhost --root=./app --input=/tmp/languages.csv
	 * 
	 * /var/www/groupoffice-server/bin/groupoffice webclient/language/import-csv -h=localhost --root=/var/www/html/groupoffice-webclient/app --input=languages.csv
	 * 
	 * /var/www/groupoffice-server/bin/groupoffice webclient/language/import-csv -h=localhost --root=/var/www/html/elearning-webclient/app --input=languages.csv
	 * 
	 * @param type $root
	 * @param type $input
	 */
	public function importCsv($root, $input) {
		$fp = fopen($input, 'r');
		
		while($record = fgetcsv($fp,0, $this->delimiter)) {
			$langFile = new LanguageFile($root.$record[2]);
			$langFile->{$record[0]} = $record[1];
			$langFile->save();
			
			echo "Importing '".$record[0]."' in file '".$root.$record[2]."'\n";
		}
		
		fclose($fp);
		
		echo "Import done!\n";
	}

}
