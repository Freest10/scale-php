<?php
	class Response {
		
		public static function goodResponse(){
			$result = [];
			$result['result'] = 'ok';
			print_r(json_encode($result));
		}
		
		public static function errorResponse($text, $codeError = 400){
			http_response_code($codeError);
			$result = [];
			$result['description'] = $text;
			$result['errorCode'] = $codeError;
			print_r(json_encode($result));
		}
	}