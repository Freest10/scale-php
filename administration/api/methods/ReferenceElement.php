<?php
namespace Api;
	class ReferenceElement {

	    private $referenes;

	    function __construct()
        {
            $this->referenes = \ClassesOperations::autoLoadClass('\Controller\References', '/controllers/References.php');
        }

        public function get($id){
            $this->referenes->getReferenceElementData($id);
		}
		
		public function set($id){
            $this->referenes->elementData = $_POST;
            $this->referenes->setReferenceElementData($id);
		}
	}