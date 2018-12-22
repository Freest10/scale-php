<?php
namespace Controller;
	class Settings{
		
		private $settingsData;
		
		public function setSettings(){
			$langModel = \Langs::getInstance();
			if(isset($this->settingsData["lang"])){
				$langModel->setActiveLang($this->settingsData["lang"]);
			}

            $this->setCommonSettings("admin_email",$this->settingsData["admin_email"]);
			\Response::goodResponse();
		}

		public function setSettingsData($settingsData = null){
		    $this->settingsData = $settingsData;
		}

		public function getSettings($notPrintJson = false){
		    $data=[];
		    $settingsReqToDb= \DataBase::justQueryToDataBase("SELECT * FROM settings");
		    while($responseFromDb = \DataBase::responseFromDataBase($settingsReqToDb))
            {
                $setting = [];
                $setting["id"] = $responseFromDb["id"];
                $setting["name"] = $responseFromDb["name"];
                $setting["value"] = $responseFromDb["value"];
                array_push($data, $setting);
            }
            if($notPrintJson){
                return $data;
            }

            \JsonOperations::printJsonFromPhp($data);
		}

		public function getAdminEmailSettings(){
            return \DataBase::queryToDataBase("SELECT value FROM settings WHERE name = 'admin_email'")["value"];
        }

		private function setCommonSettings($name, $value){
		    $isCreate = \DataBase::queryToDataBase("SELECT name FROM settings WHERE name='$name'")["name"];

		    if($isCreate){
		        \DataBase::justQueryToDataBase("UPDATE settings SET value='$value' WHERE name='$name'");
		    }else{
		        \DataBase::justQueryToDataBase("INSERT settings SET name='$name', value='$value'");
		    }
		}
		
	}