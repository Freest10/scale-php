<?php
namespace Api;
	class Langs {
		public function get(){
			$lang = \Langs::getInstance();
			return $lang->getAllLangs();
		}
	}