<?php
namespace Api;
	class MailTemplates {
		
		private $mailTemplatesModel;
		
		function __construct() {
		   $this->mailTemplatesModel = \ClassesOperations::autoLoadClass('\Controller\MailTemplates', '/controllers/MailTemplates.php');
	   }
		
		public function get($id){
			if($id){
				$this->mailTemplatesModel->getMailTemplate($id);
			}else{
				$this->mailTemplatesModel->getMailTemplates($_GET['begin'], $_GET['limit']);
			}
		}
		
		public function set($id){
			if($id){
				$this->mailTemplatesModel->setElementData($_POST);
				$this->mailTemplatesModel->setMailTemplate($id);
			}
		}		
	}