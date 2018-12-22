<?php
namespace Api;
	class Addresses {
		
		private $addressModel;
		
		function __construct() {
		   require CURRENT_WORKING_DIR . '/controllers/Address.php';
		   $this->addressModel = new \Controller\Address();
	   }
		
		public function get($id){
			$this->addressModel->getAddresses();
		}
		
		public function set(){
			$this->addressModel->updateAddressesDatas();
		}
	}