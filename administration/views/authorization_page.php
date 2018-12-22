<?php 

	class AuthorizationPage{

		public function authForm($errorMessage = ""){

			$langs = \Langs::getInstance();

			echo '
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<canvas></canvas>
			<link href="/css/administration/auth.css" rel="stylesheet">
			<link href="/css/administration/bootstrap.css" rel="stylesheet">
			<link href="/css/administration/style.css" rel="stylesheet">
			<script src="/js/administration/canvas_animation/zepto.min.js"></script>
			<script src="/js/administration/canvas_animation/index.js"></script>
			<div id="auth_wrap">
			    <div id="auth_wrap_flex">
                    <div id="auth">
                        <div class="head"><h2>'.$langs->getMessage("backend.auth.authorization").'</h2></div>
                            <div class="cont">
                                <form action="/admin/users/login_do/" method="post">
                                    <input type="hidden" name="from_page" value="/admin/events/last/">
                                    <div class="flex_block">
                                        <label><h4>'.$langs->getMessage("backend.auth.login").'</h4><input type="text" class="form-control" id="login_field" name="login"></label>
                                        <label><h4>'.$langs->getMessage("backend.auth.password").'</h4><input type="password" class="form-control" id="password_field" name="password"></label>
                                    </div>
                                    <div>
                                        <label><input type="checkbox" value="1" name="u-login-store">'.$langs->getMessage("backend.auth.remember_me").'</label>
                                        <div>
                                            <input type="submit" id="submit_field" class="btn btn-primary btn-block m-t" value="'.$langs->getMessage("backend.auth.enter").'">
                                        </div>
                                    </div>
                                    <div class="errorMessageAuth">'.$errorMessage.'</div>
                                </form>
                            </div>
                    </div>
                </div>
			</div>
			'; 
		}
	}
?>