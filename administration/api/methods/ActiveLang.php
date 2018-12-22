<?php
namespace Api{
	class ActiveLang {
		public function get(){
			$langs = \Langs::getInstance();
			return $langs->getActiveLang();
		}
	}
}