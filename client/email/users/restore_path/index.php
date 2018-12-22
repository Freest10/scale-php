<?php

use PageInfo\PageInfo as PageInfo;
use Pug\Pug;
use SystemMacros\SystemMacros as SystemMacros;
use Macros\CustomMacros as CustomMacros;
use Macros\Macros as Macros;

include_once CURRENT_WORKING_DIR . '/vendor/autoload.php';
include_once CURRENT_WORKING_DIR . '/client/custom_macros/index.php';
include_once CURRENT_WORKING_DIR . '/client/macros/index.php';
include_once CURRENT_WORKING_DIR . '/client/page_info/index.php';
include_once CURRENT_WORKING_DIR . '/client/macros/PugAdapter.php';
include_once CURRENT_WORKING_DIR . '/client/custom_macros/PugAdapter.php';
include_once CURRENT_WORKING_DIR . '/client/system_macros/index.php';

class UserRestore
{
    private $userId;
    private $userName;
    private $userHash;
    private $userPassword;
    private $templatePath;
    private $fromName;
    private $langs;
    private $sitePath;
    private $userEmail;
    private $config;

    function __construct()
    {
        $this->templatePath = CURRENT_WORKING_DIR . '/client/email/users/restore_path/pug/index.pug';
        $this->langs = \Langs::getInstance();
        $this->fromName = $this->langs->getMessage("backend.users.password_restore");
        $this->config = MainConfiguration::getInstance();
    }

    public function setSitePath($sitePath)
    {
        $this->sitePath = $sitePath;
    }

    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
    }

    public function setRestoreUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setRestoreUserName($userName)
    {
        $this->userName = $userName;
    }

    public function setRestoreUserHash($userHash)
    {
        $this->userHash = $userHash;
    }

    public function setRestoreUserPassword($userPassword)
    {
        $this->userPassword = $userPassword;
    }

    public function setRestoreModeSend($mode)
    {
        if ($mode == "password") {
            $this->templatePath = CURRENT_WORKING_DIR . '/client/email/users/restore_path/pug/restore_password.pug';
            $this->fromName = $this->langs->getMessage("backend.users.new_password");
        }
    }

    public function sendRestoreUserMessage()
    {
        $cachePath = CURRENT_WORKING_DIR . '/client/templates/cache/';
        $baseDir = CURRENT_WORKING_DIR . '/client/templates/pug';

        if ($this->isProduction()) {
            if ($this->isDeployed()) {
                $pug = new Pug(array(
                    'cache' => $cachePath,
                    'upToDateCheck' => false,
                    'basedir' => $baseDir
                ));
            } else {
                $pug = new Pug(array(
                    'cache' => $cachePath
                ));

                list($success, $errors) = $pug->cacheDirectory(CURRENT_WORKING_DIR . '/client/templates/pug');
                $this->config->set("cache_pug", "restore", "1");
            }
        } else {
            $pug = new Pug(array(
                'cache' => $cachePath,
                'expressionLanguage' => 'js',
                'basedir' => $baseDir
            ));
            $this->config->set("cache_pug", "restore", "0");
        }

        $mailResult = $pug->render($this->templatePath, $this->getGlobalVariablesToPug());
        $systemMacros = new SystemMacros();
        $systemMacros->sendEmail($mailResult, $this->userEmail, $this->fromName);
    }

    private function isProduction()
    {
        return !!$this->config->get("system", "production_mode");
    }

    private function isDeployed()
    {
        return !!$this->config->get("cache_pug", "restore");
    }

    private function getGlobalVariablesToPug()
    {
        $macros = new Macros();
        $customMacros = new CustomMacros();
        $macrosPugAdapter = new \Macros\PugAdapter($macros);
        $customMacrosPugAdapter = new \CustomMacros\PugAdapter($customMacros);
        $pageInfo = new PageInfo();
        $pageInfoCreate = $pageInfo->getPageInfo();
        $customVariables = array(
            'pageInfo' => $pageInfoCreate,
            'reqParams' => $pageInfo->getParams(),
            'userName' => $this->userName,
            'userRestoreHash' => $this->userHash,
            'userPassword' => $this->userPassword,
            'sitePath' => $this->sitePath
        );

        return array_merge($customVariables, $macrosPugAdapter->getMacros(), $customMacrosPugAdapter->getCustomMacros());
    }
}