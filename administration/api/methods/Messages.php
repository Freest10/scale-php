<?php
namespace Api;
	class Messages {

		private $messages;

        function __construct()
        {
            $this->messages = \ClassesOperations::autoLoadClass('\Controller\Messages', '/controllers/Messages.php');
        }

		public function get($id){
			if($id){
				$this->messages->getMessage($id);
			}else{
				$this->messages->getMessages($_GET['begin'], $_GET['limit']);
			}
		}

		public function delete (){
            $_DELETE = \Requests::getDELETE();
            if(is_array($_DELETE["ids"])){
                $this->messages->deleteMessages($_DELETE["ids"]);
            }
        }
	}
?>
