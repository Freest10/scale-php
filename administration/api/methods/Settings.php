<?php

namespace Api;
class Settings
{

    private $settingsModel;

    function __construct()
    {
        $this->settingsModel = \ClassesOperations::autoLoadClass('\Controller\Settings', '/controllers/Settings.php');
    }

    public function get()
    {
        $this->settingsModel->getSettings();
    }

    public function set()
    {
        $this->settingsModel->setSettingsData($_POST);
        $this->settingsModel->setSettings();
    }
}