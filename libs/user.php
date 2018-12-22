<?php
	class user {
		
		public function getUserIdByLogin($login){

			
			if($login != ''){
				$userId =  \DataBase::queryToDataBase("SELECT user_id FROM users WHERE login='".$login."'")["user_id"];

				if($userId != ''){
					return $userId;
				}else{
					return false;
				}
			}else{
				return false;
			}
			
		}
		
	}
?>