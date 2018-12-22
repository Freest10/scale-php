<?php

	class Auth{

		public function isAuth() {
    		return isset($_SESSION["login"]);
		}

		public function setAuth($life_time_session) {
			session_start(/*[
				'cookie_lifetime' => $life_time_session
			]*/);
		}

		public function deleteAuth() {
			unset($_SESSION);
		}

		//смотрим есть ли такой пользователь в БД
		public function getUserAuth($login = '', $password = ''){
			$dataBase = new \DataBase;
			if($password != '' && $login != ''){
				$userData =  $dataBase -> queryToDataBase("SELECT password, is_admin FROM users WHERE login='".$login."'");
				//если есть пользователь с таким логином, то сверяем пароль для него
				if($userData['password'] != '' && $userData['is_admin'] == 1){
				    return password_verify($password, $userData['password']);
				}else{
					return false;
				}
			}
		}
	}

?>