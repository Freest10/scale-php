<?php
namespace Api;
	class LogOut {
		public function get(){
			$Session = new \Session();
			$Session->deleteCookie();
			$session_id = $Session -> getSessionId();
			$Session -> deleteSessionFromDb($session_id);
			$Session -> destroySession();
			\Response::goodResponse();
		}
	}