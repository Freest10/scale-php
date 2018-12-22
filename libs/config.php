<?php 

	if (!defined("CURRENT_WORKING_DIR")) {
		define("CURRENT_WORKING_DIR", str_replace("\\", "/", dirname(dirname(__FILE__))));
	}

	if(!defined('CONFIG_INI_PATH')) {
		define('CONFIG_INI_PATH', CURRENT_WORKING_DIR . '/config.ini');
	}
	
	if (!defined('PHP_VERSION_ID')) {
		$version = explode('.', PHP_VERSION);

		define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
	}
	
	if(!class_exists('MainConfiguration')) {
		require CURRENT_WORKING_DIR . '/libs/configuration.php';
	}

	try {
		$config = MainConfiguration::getInstance();
	} catch (Exception $e) {
		echo 'Critical error: ', $e->getMessage();
		exit;
	}