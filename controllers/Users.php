<?php

namespace Controller;

use SitePaths;

class Users
{
    private $user;
    private $id;
    private $struct_data;
    private $page;
    private $langs;
    private $fieldsController;

    function __construct()
    {
        $this->langs = \Langs::getInstance();
        $this->fieldsController = \ClassesOperations::autoLoadClass('\Controller\Fields', '/controllers/Fields.php');
    }

    public function getAllUsers()
    {

        $dataToJsonLimit = [
            'selectResponse' => 'SELECT user_id as id, login as name FROM `users` ORDER BY id DESC',
            'countResponse' => 'Users',
            'templatesData' => [
                'id' => true,
                'name' => true,
            ],
            'begin' => $_GET['begin'],
            'limit' => $_GET['limit'],
            'type' => 'Users'
        ];
        \JsonOperations::createLimitJson($dataToJsonLimit);

    }

    public function setUserData($id)
    {
        $this->id = $id;
        \DataBase::queryToDataBase("UPDATE users SET login='" . $_POST['name'] . "' WHERE user_id =" . $id);
        $passwordsArray = $this->getPasswordAndConfirmPasswordFromRequestFields();

        if ($this->comparePasswords($passwordsArray["password"], $passwordsArray["confirmPassword"])) {
            if ($passwordsArray["password"] && $passwordsArray["confirmPassword"]) {
                $this->setPasswordFromFields($passwordsArray["password"]);
            }

            $struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
            $struct_data->structId = $this->id;
            $struct_data->structTypeId = 3;
            $struct_data->setFieldsData($_POST);
            \Response::goodResponse();
        } else {
            \Response::errorResponse($this->langs->getMessage("backend.errors.password_mismatch"), '-301');
        }
    }

    public function createHashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function createUser()
    {
        $this->createRandomUser();
        \Response::goodResponse();
    }

    public function getIdOfCreatedRandomUser()
    {
        $this->createRandomUser();
        return \DataBase::queryToDataBase("SELECT max(user_id) as id FROM users")['id'];
    }

    public function createUserWithParams($login, $password, $confirmPassword, $email, $isAdmin)
    {
        if ($password === $confirmPassword) {
            $passwordHash = $this->createHashPassword($password);
            $today = date("Y-m-d H:i:s");
            if ($isAdmin !== 1) {
                $isAdmin = 0;
            }
            \DataBase::queryToDataBase("INSERT users SET login='$login', password = '$passwordHash', is_admin=$isAdmin, new_event=1, date='$today'");
            if ($email) {
                $userId = $this->getLastUserId();
                $fields = [];
                $fields["email_user"] = $email;
                $this->setUserFields($userId, $fields);
            }
        }
    }

    public function deleteUser($id, $notResponse = false)
    {
        \DataBase::queryToDataBase("DELETE FROM users WHERE user_id =" . $id);
        $pageController = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
        $pageController->deleteAllFieldsByPage($id, 3);
        if ($notResponse) {
            return true;
        }
        \Response::goodResponse();
    }

    public function getUserData($id)
    {
        $this->user = [];
        $this->id = $id;
        $this->user['name'] = $this->getUserName($this->id);
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $this->struct_data->structId = $id;
        $this->struct_data->structTemplateId = 3;
        $this->struct_data->structTypeId = 3;
        $this->struct_data->generateStructData();
        $this->user['groups'] = [$this->struct_data->createGeneralGroup()];
        $this->user['groups'] = array_merge($this->user['groups'], $this->struct_data->structData['groups']);
        $this->getIsAdmin();
        $this->getPasswordsField();
        $this->getConfirmPasswordsField();
        $this->user['fields'] = array_merge($this->user['fields'], $this->struct_data->structData['fields']);

        $eventsController = \ClassesOperations::autoLoadClass('\Controller\Events', '/controllers/Events.php');
        $eventsController->newEvent($id, "user_id", "users", 0);
        \JsonOperations::printJsonFromPhp($this->user);
    }

    public function getUserName($id)
    {
        return \DataBase::queryToDataBase("SELECT login FROM users WHERE user_id = " . $id)['login'];
    }

    public function getUsersForClient($options)
    {
        $ids = $options["ids"];
        $props = $options["props"];
        $groups = $options["groups"];
        $idsToSql = "";
        if (count($ids) > 0) {
            foreach ($ids as $key => $value) {
                if ($key != 0) {
                    $idsToSql .= " OR";
                }
                $idsToSql .= " usr.user_id = " . $value;
            }
        }

        $typesQueryToDbQuery = \DataBase::justQueryToDataBase("
				SELECT usr.user_id as id, usr.login as login
				FROM users usr
				WHERE " . $idsToSql
        );
        $users = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($typesQueryToDbQuery)) {
            $data = [];
            $dataId = $responseFromDb["id"];
            $data["login"] = $responseFromDb["login"];
            if (count($props) > 0) {
                if (!$this->struct_data) {
                    $this->connectStructData();

                }
                $this->struct_data->structId = $dataId;
                $this->struct_data->structTemplateId = 3;
                $this->struct_data->structTypeId = 3;
                $data["props"] = $this->struct_data->getFieldsByPageIdForClient($props);
            }

            if (count($groups) > 0) {

                if (!$this->struct_data) {
                    $this->connectStructData();
                }
                $this->struct_data->structId = $dataId;
                $this->struct_data->structTypeId = 3;
                $this->struct_data->structTemplateId = 3;
                $data["groups"] = $this->struct_data->getGroupsByPageIdForClient($groups);
            }

            $users[$dataId] = $data;
        }

        return $users;
    }

    private function createRandomUser()
    {
        $today = date("Y-m-d H:i:s");
        $userName = "new_User" . rand(1, 1000);
        \DataBase::queryToDataBase("INSERT users SET login='$userName', new_event=1, date='$today'");
    }

    private function connectStructData()
    {
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $this->page = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
    }

    public function getUserIdBySessionId($sessionId)
    {
        return \DataBase::queryToDataBase("SELECT user_id as 'id' FROM sessions WHERE session_id ='" . $sessionId . "'")["id"];
    }

    public function getUserId()
    {
        include_once CURRENT_WORKING_DIR . '/libs/root-src/session.php';
        $sessionId = \Session::getSessionId();
        $userId = 0;
        if ($sessionId) {
            $userReqId = $this->getUserIdBySessionId($sessionId);
            if ($userReqId > 0) {
                $userId = $userReqId;
            }
        }
        return $userId;
    }

    public function getUserAccessesForSection($userId, $sectionName)
    {
        return \DataBase::queryToDataBase("SELECT read_right as 'get', create_right as 'put', edit_right as 'set', delete_right as 'delete' FROM main_rights WHERE section_text_id= '$sectionName' AND user_id= $userId");
    }

    public function getUserAccessesForPlugin($userId, $pluginName)
    {
        return \DataBase::queryToDataBase("SELECT read_right as 'get', create_right as 'put', edit_right as 'set', delete_right as 'delete' FROM plugins_rights WHERE text_id= '$pluginName' AND user_id= $userId");
    }

    public function createUserClient($options)
    {
        if ($options["password"] !== $options["passwordConfirm"]) {
            return ["errorCode" => "PASSWORD_MISMATCH",
                "description" => $this->langs->getMessage("backend.errors.password_mismatch")];
        }

        if (!$options["login"]) {
            return ["errorCode" => "THERE_IS_NOT_LOGIN",
                "description" => $this->langs->getMessage("backend.errors.there-is-not-login")];
        }

        if ($this->isHasUserLogin($options["login"])) {
            return ["errorCode" => "USER_ALREADY_EXIST",
                "description" => $this->langs->getMessage("backend.errors.user-already-exist")];
        }

        $this->createUserDb($options);
        $lastUserId = $this->getLastUserId();
        $this->setUserFields($lastUserId, $options["fields"]);

        return true;
    }

    public function editUserClient($options)
    {
        if ($options["id"] && ($options["password"] == $options["passwordConfirm"])) {

            if (!$this->isHasUserId($options["id"])) {
                return false;
            }

            if (!$this->isGoodUserPassword($options["id"], $options["password"])) {
                return false;
            }

            $this->setUserFields($options["id"], $options["fields"]);

            return true;
        }
    }

    public function logOutUser()
    {
        include_once CURRENT_WORKING_DIR . '/libs/root-src/session.php';
        $Session = new \Session;
        $Session->deleteCookie();
        $session_id = $Session->getSessionId();
        $Session->deleteSessionFromDb($session_id);
        $Session->destroySession();
        return true;
    }

    public function getUserIdByLogin($login)
    {
        if ($login != '') {
            $userId = \DataBase::queryToDataBase("SELECT user_id FROM users WHERE login='" . $login . "'")["user_id"];

            if ($userId != '') {
                return $userId;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function authUser($options)
    {
        include_once CURRENT_WORKING_DIR . '/libs/root-src/auth.php';
        include_once CURRENT_WORKING_DIR . '/libs/root-src/session.php';
        $session = new \Session;
        $authorization = new \Auth;

        if (!$authorization->getUserAuth($options['login'], $options['password'])) {
            return ["errorCode" => "NOT_AUTH",
                "description" => $this->langs->getMessage("backend.errors.not-auth")];
        }

        $userId = $this->getUserIdByLogin($options['login']);
        if ($userId) {
            $life_time_session = 0;
            if ($options['rememberMe'] == 1) {
                $life_time_session = 2592000;
            }

            session_set_cookie_params($life_time_session);
            $session->deleteOldSessionFromDataBase();
            $authorization->setAuth($life_time_session);
            $sessionId = session_id();
            $session->setSession($sessionId, $userId);
            $session->closeSession();

            return true;
        }
    }

    public function restoreUser($options)
    {
        $emailFieldId = $this->getFieldIdByFieldName("email_user");
        $userId = \DataBase::queryToDataBase("SELECT page_id as 'id' FROM field_values_type_string WHERE value = '" . $options["email"] . "' AND type=3 AND field_id=$emailFieldId")["id"];
        if ($userId > 0) {
            if ($this->isCouldSend($userId)) {
                include_once CURRENT_WORKING_DIR . '/client/email/users/restore_path/index.php';
                $restoreUserMail = new \UserRestore();

                $restoreUserMail->setRestoreUserId($userId);

                $userName = $this->getUserName($userId);

                $restoreUserMail->setRestoreUserName($userName);

                $userRestorePath = $this->getRestoreHashByUser($userId, $userName);

                $restoreUserMail->setRestoreUserHash($userRestorePath);

                $restoreUserMail->setSitePath(SitePaths::getSitePath());

                $restoreUserMail->setUserEmail($options["email"]);

                $restoreUserMail->sendRestoreUserMessage();
                return true;
            }
            return false;
        }

        return ["errorCode" => "USER_EMAIL_NOT_FOUND",
            "description" => $this->langs->getMessage("backend.errors.user-email-not-found")];
    }

    public function getUserMainRights($id)
    {
        $userMainRightsQueryToDbQuery = $this->reqMainRights($id);
        $mainRights = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($userMainRightsQueryToDbQuery)) {
            $sectionTextId = $responseFromDb['section_text_id'];
            $mainRights[$sectionTextId] = [];
            $mainRights[$sectionTextId]['read_right'] = $responseFromDb['read_right'];
            $mainRights[$sectionTextId]['create_right'] = $responseFromDb['create_right'];
            $mainRights[$sectionTextId]['edit_right'] = $responseFromDb['edit_right'];
            $mainRights[$sectionTextId]['delete_right'] = $responseFromDb['delete_right'];
        }
        return $mainRights;
    }

    public function setUserRights($id, $data)
    {
        foreach ($data as $value) {
            if ($this->hasSectionRightForUser($id, $value['section_text_id'])) {

                $this->updateAccess($id, $value['section_text_id'], $value['accesses']);
            } else {
                $this->insertAccess($id, $value['section_text_id'], $value['accesses']);
            }
        }
    }

    private function insertAccess($id, $sectionTextId, $accesses)
    {
        \DataBase::queryToDataBase("INSERT main_rights SET read_right=" . $accesses['read_right'] . ", edit_right=" . $accesses['edit_right'] . ", create_right=" . $accesses['create_right'] . ", delete_right=" . $accesses['delete_right'] . ", user_id= $id, section_text_id= '$sectionTextId'");
    }

    private function updateAccess($id, $sectionTextId, $accesses)
    {
        \DataBase::queryToDataBase("UPDATE main_rights SET read_right=" . $accesses['read_right'] . ", edit_right=" . $accesses['edit_right'] . ", create_right=" . $accesses['create_right'] . ", delete_right=" . $accesses['delete_right'] . " WHERE user_id= $id AND section_text_id= '$sectionTextId'");
    }

    private function hasSectionRightForUser($id, $sectionTextId)
    {
        return \DataBase::queryToDataBase("SELECT * FROM main_rights WHERE user_id= $id AND section_text_id= '$sectionTextId'") ? true : false;
    }

    private function reqMainRights($id)
    {
        return \DataBase::justQueryToDataBase("SELECT * FROM main_rights WHERE user_id= $id");
    }

    private function isCouldSend($userId)
    {
        $today = date("Y-m-d H:i:s");
        $lastRecord = $this->getRestoreDateForUser($userId);
        if (!$lastRecord) {
            return true;
        }
        $lastRecordDate = strtotime($lastRecord);
        $dateToRecord = strtotime($today);
        if (($dateToRecord - $lastRecordDate) > 1) {
            return true;
        }
        return false;
    }

    private function getRestoreDateForUser($id)
    {
        return \DataBase::queryToDataBase("SELECT restore_date FROM users WHERE user_id = " . $id)["restore_date"];
    }

    private function setPasswordFromFields($password)
    {
        $this->setUserPassword($this->id, $password);
    }

    private function getRestoreHashByUser($id, $userName)
    {
        $stringToHash = $id;
        $stringToHash .= $userName;
        $today = date("Y_m_d_H_i_s");
        $stringToHash .= $today;
        $hashVal = hash("sha256", $stringToHash);
        $this->setRestoreHashByUser($id, $hashVal);
        return $hashVal;
    }

    private function setRestoreHashByUser($id, $hash)
    {
        $today = date("Y-m-d H:i:s");
        \DataBase::queryToDataBase("UPDATE users SET restore_path='$hash', restore_date='$today' WHERE user_id = " . $id);
    }

    public function setRecoveryPasswordToUser($options)
    {
        $hash = $options['userHash'];
        if ($hash) {
            $userDataArr = $this->getUserIdNameByRestoreHash($hash);
            $userId = $userDataArr["id"];
            if ($userId > 0) {
                $password = $options['password'];
                $passwordConfirm = $options['passwordConfirm'];
                if ($password === $passwordConfirm && $password) {
                    $this->setUserPassword($userId, $password);
                    $this->deleteRestoreUserData($userId);
                } else {
                    return ["errorCode" => "PASSWORD_MISMATCH",
                        "description" => $this->langs->getMessage("backend.errors.password_mismatch")];
                }

                return true;
            }

            return ["errorCode" => "USER_NOT_FOUND",
                "description" => $this->langs->getMessage("backend.errors.user-not-found")];
        }

        return ["errorCode" => "NO_HASH",
            "description" => $this->langs->getMessage("backend.errors.no-hash-user")];
    }

    private function getFieldIdByFieldName($fieldName)
    {
        return $this->fieldsController->getIdFieldByTextId($fieldName);
    }

    private function deleteRestoreUserData($id)
    {
        return \DataBase::queryToDataBase("UPDATE users SET restore_path=NULL, restore_date=NULL WHERE user_id = " . $id);
    }

    private function getUserIdNameByRestoreHash($hash)
    {
        return \DataBase::queryToDataBase("SELECT user_id as 'id', login FROM users WHERE restore_path = '" . $hash . "'");
    }

    private function isHasUserId($id)
    {
        return \DataBase::queryToDataBase("SELECT user_id FROM users WHERE user_id =" . $id)["user_id"];
    }

    private function isGoodUserPassword($id, $password)
    {
        $hashPasswordDb = $this->getHashUserPassword($id);

        if (password_verify($password, $hashPasswordDb)) {
            return true;
        }
        return false;
    }

    private function getHashUserPassword($id)
    {
        return \DataBase::queryToDataBase("SELECT password FROM users WHERE user_id =" . $id)["password"];
    }

    private function getLastUserId()
    {
        return \DataBase::queryToDataBase("SELECT user_id as 'id' FROM users ORDER BY id DESC LIMIT 1")["id"];
    }

    private function setUserFields($id, $fields)
    {

        $templateType = \ClassesOperations::autoLoadClass('\Controller\TemplatesType', '/controllers/TemplatesType.php');
        $templateType->getTemplateFields(3);

        $messagesController = \ClassesOperations::autoLoadClass('\Controller\Messages', '/controllers/Messages.php');

        if ($messagesController->isHaveAllNecessarilyFields($templateType->template["fields"], $fields)) {
            $this->setUserFieldValues($id, $fields, $templateType->template["fields"]);
            return true;
        }

    }

    private function createUserDb($options)
    {
        $password = $this->createHashPassword($options["password"]);
        $today = date("Y-m-d H:i:s");
        \DataBase::justQueryToDataBase("INSERT users SET password='$password', date='$today', login='" . $options["login"] . "', new_event = 1");
    }

    private function isHasUserLogin($userLogin)
    {
        return \DataBase::queryToDataBase("SELECT user_id as 'id' FROM users WHERE login ='" . $userLogin . "'")["id"];
    }

    private function setUserFieldValues($id, $fieldValues, $templateFields)
    {
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $this->struct_data->structId = $id;
        $this->struct_data->structTemplateId = 3;
        $this->struct_data->structTypeId = 3;
        $this->struct_data->setFieldsDataByTemplate($fieldValues, $templateFields);
    }

    private function getIsAdmin()
    {
        if (!is_array($this->user["fields"])) $this->user["fields"] = [];
        $fieldNum = count($this->user["fields"]);
        $this->user["fields"][$fieldNum]["id"] = -301;
        $this->user["fields"][$fieldNum]["parentId"] = 0;
        $this->user["fields"][$fieldNum]["typeId"] = 3;
        $this->user["fields"][$fieldNum]["necessarily"] = 0;
        $this->user["fields"][$fieldNum]["name"] = $this->langs->getMessage("backend.users.access_to_the_admin_panel");
        $this->user["fields"][$fieldNum]["value"] = $this->getAdminAccess($this->id);
    }

    private function getPasswordsField()
    {
        if (!is_array($this->user["fields"])) $this->user["fields"] = [];
        $fieldNum = count($this->user["fields"]);
        $this->user["fields"][$fieldNum]["id"] = -302;
        $this->user["fields"][$fieldNum]["parentId"] = 0;
        $this->user["fields"][$fieldNum]["typeId"] = 15;
        $this->user["fields"][$fieldNum]["necessarily"] = 0;
        $this->user["fields"][$fieldNum]["name"] = $this->langs->getMessage("backend.users.password");
        $this->user["fields"][$fieldNum]["value"] = "";
    }

    private function getConfirmPasswordsField()
    {
        if (!is_array($this->user["fields"])) $this->user["fields"] = [];
        $fieldNum = count($this->user["fields"]);
        $this->user["fields"][$fieldNum]["id"] = -303;
        $this->user["fields"][$fieldNum]["parentId"] = 0;
        $this->user["fields"][$fieldNum]["typeId"] = 15;
        $this->user["fields"][$fieldNum]["necessarily"] = 0;
        $this->user["fields"][$fieldNum]["name"] = $this->langs->getMessage("backend.users.confirm_password");
        $this->user["fields"][$fieldNum]["value"] = "";
    }

    private function getAdminAccess($id)
    {
        return \DataBase::queryToDataBase("SELECT is_admin FROM users WHERE user_id = " . $this->id)['is_admin'];
    }

    private function comparePasswords($password, $confirmPassword)
    {
        return $password === $confirmPassword;
    }

    private function getPasswordAndConfirmPasswordFromRequestFields()
    {
        $password = NULL;
        $confirmPassword = NULL;

        foreach ($_POST['fields'] as $value) {
            if ($value['id'] == -302) {
                if ($value['value'] != '') $password = $value['value'];
            }
            if ($value['id'] == -303) {
                if ($value['value'] != '') $confirmPassword = $value['value'];
            }
        }

        return ["password" => $password, "confirmPassword" => $confirmPassword];
    }

    private function setUserPassword($userId, $password)
    {
        \DataBase::justQueryToDataBase("UPDATE users SET password = '" . $this->createHashPassword($password) . "' WHERE user_id=" . $userId);
    }
}