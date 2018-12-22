<?php
$uri_massive = \Requests::getUriMassive();
$authorization = new Auth;
$session = new Session;
$userSession = $session->getSessionFromDataBase();

function getTranslateLetter($key)
{
    $lang = \Langs::getInstance();
    return $lang->getMessage($key);
}

if ($userSession[0]) {
    if ($uri_massive[2] != 'logged') {
        //перенаправляем на страницу событий, после успешного создания сессии
        header("Location: /admin/logged/");
    }

    //Если запрос к апи
    if ($uri_massive[3] == 'api') {
        require CURRENT_WORKING_DIR . '/administration/api/index.php';
        //перенаправляем на страницу событий, после успешного создания сессии
    } else if ($uri_massive[3] == 'config' && $uri_massive[4] == 'filemanager.config.json') {
        //перенаправляем на файловый менеджер
        require CURRENT_WORKING_DIR . '/config/filemanager.config.json';
    } else if ($uri_massive[3] == 'config' && $uri_massive[4] == 'filemanager.config.default.json') {
        //перенаправляем на файловый менеджер
        require CURRENT_WORKING_DIR . '/config/filemanager.config.default.json';
    } else if ($uri_massive[3] == 'filemanger') {
        //перенаправляем на файловый менеджер
        require CURRENT_WORKING_DIR . '/administration/views/filemanager/main.php';
    } else {
        //перенаправляем на админку, если авторизировались
        require CURRENT_WORKING_DIR . '/administration/views/main/main.php';
    }

} else {
    //Если запрос к апи
    if ($uri_massive[3] == 'api') {
        //Не авторизованный пользователь
        header("HTTP/1.0 401 Not Auth");
    } else {
        require CURRENT_WORKING_DIR . '/administration/views/authorization_page.php';
        //если нет, то показываем форму логирования
        //отправляем данные на логирование
        if ($uri_massive[2] == 'users' && $uri_massive[3] == 'login_do') {

            if ((isset($_POST['login']) && isset($_POST['password'])) && ($_POST['login'] != '' && $_POST['password'] != '')) {

                if ($authorization->getUserAuth($_POST['login'], $_POST['password'])) {
                    //правильное имя пользователя и пароль - устанавливаем сессию
                    $userVar = new user;
                    $userId = $userVar->getUserIdByLogin($_POST['login']);
                    if ($userId) {
                        $life_time_session = 0;
                        //Устанавливаем сессию на 30 дней
                        if ($_POST['u-login-store'] == 1) {
                            $config = MainConfiguration::getInstance();
                            $life_time_session = $config->get("system", "session-lifetime");
                        }

                        session_set_cookie_params($life_time_session);
                        //удаляем сессии из базы, которым больше 30 дней
                        $session->deleteOldSessionFromDataBase();
                        $authorization->setAuth($life_time_session);
                        $sessionId = session_id();
                        //устанавливаем сессию
                        $session->setSession($sessionId, $userId);
                        $session->closeSession();
                        //перенаправляем на страницу событий, после успешного создания сессии
                        header("Location: /admin/logged/");
                    }

                } else {
                    //форма авторизации
                    $authorizationPage = new AuthorizationPage;
                    //выводим форму авторизации
                    $authorizationPage->authForm(getTranslateLetter('backend.errors.error_login_or_password'));
                };
            } else {
                //форма авторизации
                $authorizationPage = new AuthorizationPage;
                //выводим форму авторизации
                $authorizationPage->authForm(getTranslateLetter('backend.errors.error_login_or_password'));
                return false;
            }

        } else {
            //форма авторизации
            $authorizationPage = new AuthorizationPage;
            //выводим форму авторизации
            $authorizationPage->authForm();
        }
    }
}