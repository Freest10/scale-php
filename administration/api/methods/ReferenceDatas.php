<?php
namespace Api;
	class ReferenceDatas {
		public function get($id){
			$referenceModel = \ClassesOperations::autoLoadClass('\Controller\References', '/controllers/References.php');
			$referenceModel->getReferenceDatas($id);
		}
		
		public function set($id){
			$referenceModel = \ClassesOperations::autoLoadClass('\Controller\References', '/controllers/References.php');
			$referenceModel->setReferenceDatas($id);
		}
	}
?>