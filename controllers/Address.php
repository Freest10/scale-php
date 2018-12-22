<?php
namespace Controller;
	class Address {

	    function __construct()
        {
            require CURRENT_WORKING_DIR . '/controllers/References.php';
        }

        public function getAddresses(){
			$referencemodel = new References();
			$referencemodel->getReferenceDatasForAddress(54);
		}
		
		public function updateAddressesDatas(){
			$referencemodel = new References();
			$referencemodel->setReferenceDatasForAddresses(54);
		}
	}