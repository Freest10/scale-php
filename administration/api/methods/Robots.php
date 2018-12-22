<?php
namespace Api;
	class Robots{

	    private $robotsController;

        function __construct() {
           $this->robotsController = \ClassesOperations::autoLoadClass('\Controller\Robots', '/controllers/Robots.php');
       }

		public function get(){
			$this->robotsController->getRobots();
		}

		public function set(){
			 $this->robotsController->setRobots($_POST["data"]);
		}
	}