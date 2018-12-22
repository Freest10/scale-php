<?php
namespace Api;
	class References {
		public function get(){
			$referenes_model = \ClassesOperations::autoLoadClass('\Controller\References', '/controllers/References.php');
			$referenes_model->getReferenceLimit($_GET['begin'], $_GET['limit']);
		}
	}