<?php 
	class Session{
		
		static function getSessionId() {
			$sessionId = $_COOKIE[session_name()];
			if($sessionId){
				return $sessionId;	
			}else{
				return false;	
			}
		}
		
		public function setSession($sessionId,$userId) {
			\DataBase::queryToDataBase("INSERT INTO sessions VALUES ('".$sessionId."','".$userId."', '".date('Y-m-d H:i:s')."')");
		}
		
		public function closeSession(){
			session_write_close();
		}
		
		public function getSessionFromDataBase(){
			$sessionId = $this->getSessionId();
			$sessionData = \DataBase::queryToDataBase("SELECT * FROM sessions WHERE session_id='".$sessionId."'", MYSQLI_NUM);
			if($sessionData != ''){
				return $sessionData;
			}else{
				return false;
			}
		}
		
		public function deleteCookie(){
			setcookie ("PHPSESSID", "", time() - 3600, '/');
		}
		
		public function deleteOldSessionFromDataBase(){
			$sessionData = \DataBase::justQueryToDataBase("SELECT * FROM sessions");
			$datetimeToday = new DateTime(date());
			while($responseFromDb = \DataBase::responseFromDataBase($sessionData)) // перебор строк таблицы с начала до конца
			{
				$datetimeSessionCreate = new DateTime($responseFromDb['date_time_session_create']);
				$interval = $datetimeSessionCreate->diff($datetimeToday);
				if($interval->format('%a') > 30){
					$this->deleteSessionFromDb($responseFromDb['session_id']);
				}
			}
		}
		
		public function deleteSessionFromDb($sess_id){
			$deleteSessionFromDB = \DataBase::justQueryToDataBase("DELETE FROM sessions WHERE session_id ='".$sess_id."'");
		}
		
		public function destroySession(){
			session_destroy();
		}
	}
?>