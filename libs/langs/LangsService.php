<?php

class LangsService implements LangsInterface
{
    private $activeLangTextId;
    private $langs;
    private $nameSpaceToPath = [];
    private $langJsonDataPhp = [];

    function __construct()
    {
        $this->setActiveLangTextId();
        $this->nameSpaceToPath['main'] = '/js/administration/json/i18n/';
        $this->nameSpaceToPath['plugins'] = '/plugins/%folderName%/i18n/';
    }

    public function getAllLangs()
    {
        $reqToDb = \DataBase::justQueryToDataBase("SELECT * FROM langs");
        $this->langs = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($reqToDb)) {
            $data = [];
            $data["id"] = $responseFromDb["id"];
            $data["text_id"] = $responseFromDb["text_id"];
            $data["name"] = $responseFromDb["name"];
            if ($responseFromDb["active"]) {
                $data["active"] = $responseFromDb["active"];
            }
            $this->langs[] = $data;
        }

        \JsonOperations::printJsonFromPhp($this->langs);
    }

    public function getActiveLang()
    {
        \JsonOperations::printJsonFromPhp($this->getActiveLangData());
    }

    public function getActiveLangData()
    {
        return \DataBase::queryToDataBase("SELECT * FROM langs WHERE active = 1");
    }

    public function setActiveLang($id)
    {
        $this->removeActiveLang();
        \DataBase::justQueryToDataBase("UPDATE langs SET active = 1 WHERE id = " . $id);
        $this->setActiveLangToConfig();
        $this->setActiveLangTextId();
    }

    public function setActiveLangByTextId($textId)
    {
        $this->removeActiveLang();
        \DataBase::justQueryToDataBase("UPDATE langs SET active = 1 WHERE text_id = " . $textId);
        $this->setActiveLangToConfig();
        $this->setActiveLangTextId();
    }

    public function setActiveLangToConfigByTextId($textId)
    {
        $config = MainConfiguration::getInstance();
        $config->set("system", "lang", $textId);
        $this->setActiveLangTextId();
    }

    public function getLangData($nameSpace = 'main', $folderName= null)
    {
        if (isset($this->langJsonDataPhp[$nameSpace])) {
            return $this->langJsonDataPhp[$nameSpace];
        }

        $text_id_active_lang = $this->getActiveLangTextId();
        $path = $this->getPathByNameSpace($nameSpace);
        if (!$path) {
            return false;
        }

        if($folderName){
            $path = str_replace('%folderName%', $folderName, $path);
            $nameSpace .= '_'.$folderName;
        }

        $file_path = CURRENT_WORKING_DIR . $path . $text_id_active_lang . ".json";
        $langJsonData = file_get_contents($file_path);
        $this->langJsonDataPhp[$nameSpace] = json_decode($langJsonData);
        return $this->langJsonDataPhp[$nameSpace];
    }

    public function pushLangDataNameSpace($nameSpace, $path)
    {
        if (isset($this->nameSpaceToPath[$nameSpace])) {
            return false;
        }
        $this->nameSpaceToPath[$nameSpace] = $path;
    }

    public function getMessage($key, $nameSpace = 'main', $folderName = null)
    {
        $explKeys = explode(".", $key);
        $data = $this->getLangData($nameSpace, $folderName);
        if (!$key) {
            return "No key specified";
        } else if (count($explKeys) > 0) {
            return $this->getMessageFromData($explKeys, $data, 0, $folderName);
        }
    }

    public function getPluginMessage($textId, $key){
        return $this->getMessage($key, 'plugins', $textId);
    }

    private function getPathByNameSpace($nameSpace)
    {
        return $this->nameSpaceToPath[$nameSpace];
    }

    private function getMessageFromData($keyArr, $data, $index, $folderName= null)
    {
        $key = $keyArr[$index];
        $nwIndex = ++$index;
        if (gettype($data->$key) == "object") {
            return $this->getMessageFromData($keyArr, $data->$key, $nwIndex, $folderName);
        } else {
            return $data->$key;
        }
    }

    private function setActiveLangTextId()
    {
        $activeLangFromDb = $this->getActiveLangData();
        if ($activeLangFromDb) {
            $this->activeLangTextId = $activeLangFromDb['text_id'];
            return true;
        }

        $config = MainConfiguration::getInstance();
        $this->activeLangTextId = $config->get("system", "lang");
        return true;
    }

    private function setActiveLangToConfig()
    {
        $activeLang = \DataBase::queryToDataBase("SELECT text_id FROM langs WHERE active = 1")["text_id"];
        $config = MainConfiguration::getInstance();
        $config->set("system", "lang", $activeLang);
    }

    private function removeActiveLang()
    {
        \DataBase::justQueryToDataBase("UPDATE langs SET active = 0 WHERE active = 1");
    }

    private function getActiveLangTextId()
    {
        if (!$this->activeLangTextId) {
            $this->setActiveLangTextId();
        }

        return $this->activeLangTextId;
    }

}