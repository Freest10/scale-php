<?php
namespace Api;
	class Emarket {
		
		private $emarket;
		
		function __construct(){
			$this->emarket = \ClassesOperations::autoLoadClass('\Controller\Emarket','/controllers/Emarket.php');
		}
		
		public function get($id){
			if($id){
				$this->emarket -> getOrderById($id);
			}else{
				$this->emarket -> getOrders();
			}
		}
		
		public function delete($id){
            $this->emarket->deleteOrder($id);
		}
		
		public function set($id){
			$this->emarket -> setOrder($id);
		}
	}