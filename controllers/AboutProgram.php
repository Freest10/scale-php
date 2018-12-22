<?php
namespace Controller{
	class AboutProgram {

        private $files;

        function __construct() {
           require_once CURRENT_WORKING_DIR . '/libs/systems_classes/files.php';
           $this->files = new \FilesOperations;
        }

		public function getInfo(){
           $data= $this->files->readFile("libs/root-src/about_program.json", true);
           echo $data;
		}

	}
}