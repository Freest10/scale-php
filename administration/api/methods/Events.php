<?php
namespace Api;
	class Events {

		private $events;

		function __construct() {
		   $this->events = \ClassesOperations::autoLoadClass('\Controller\Events', '/controllers/Events.php');
	   }

		public function get(){
			$this->events->getEvents();
		}

	}