<?php
namespace Controller;
	class Events {

		public function getEvents(){
            $events = [];
            $events["orders"]=$this->getOrdresEvents();
            $events["messages"]=$this->getWebFormsEvents();
            $events["users"]=$this->getUsersEvents();
            \JsonOperations::printJsonFromPhp($events);
		}

		private function getOrdresEvents(){
            $reqToDB = \DataBase::justQueryToDataBase("SELECT order_id as 'id', order_id as 'text', date FROM  orders WHERE new_event=1 ORDER BY date DESC LIMIT 5");
            return  $this->getArrEventsFromReq($reqToDB);
        }

		private function getWebFormsEvents(){
		    $reqToDB = \DataBase::justQueryToDataBase("SELECT msgs.id as 'id', tin.name as 'text', msgs.date FROM  messages msgs INNER JOIN template_id_name tin ON msgs.template_id=tin.id WHERE new_event=1 ORDER BY msgs.date DESC LIMIT 5");
		    return  $this->getArrEventsFromReq($reqToDB);
		}

		private function getUsersEvents(){
            $reqToDB = \DataBase::justQueryToDataBase("SELECT user_id as 'id', login as 'text', date FROM  users WHERE new_event=1 ORDER BY date DESC LIMIT 5");
            return  $this->getArrEventsFromReq($reqToDB);
        }

		private function getArrEventsFromReq($reqToDB){
            $events = [];
            $eventsNum = 0;
            while($responseFromDb = \DataBase::responseFromDataBase($reqToDB))
            {
                $events[$eventsNum] = [];
                $events[$eventsNum]["id"]=$responseFromDb["id"];
                $events[$eventsNum]["date"]=$responseFromDb["date"];
                $events[$eventsNum]["text"]=$responseFromDb["text"];
                $eventsNum++;
            }
            return $events;
		}

		public function newEvent($id, $idName, $tableName, $value){
		    if($id && $tableName){
		        if(!$idName) $idName = "id";
                if(!$value) $value=0;
                \DataBase::justQueryToDataBase("UPDATE $tableName SET new_event=$value WHERE $idName=$id");
		    }
		}

	}
?>