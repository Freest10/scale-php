<?php
namespace Api;
	class FilemanagerLanguage {
		public function get(){
			$lang = 'en';
			if(isset($_GET["lang"])){
				$lang = $_GET["lang"];
			}
			require CURRENT_WORKING_DIR . '/js/administration/json/filemanager/languages/'.$lang.'.json';
		}
	}
?>