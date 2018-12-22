<?php

namespace Controller;
class Sections
{
    private $langs;

    function __construct()
    {
        $this->langs = \Langs::getInstance();
    }

    public function getSections()
    {
        $sectionsQueryToDbQuery = $this->getSectionsFromDbForUser();
        $sections = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($sectionsQueryToDbQuery)) {
            $data = [];
            $data["id"] = $responseFromDb["id"];
            $translateString = "frontend.mainMenu.";
            $translateString .= $responseFromDb["name"];
            $data["name"] = $this->langs->getMessage($translateString);
            $data["link"] = $responseFromDb["link"];
            if ($responseFromDb["class_ico"]) {
                $data["classIco"] = "fa ";
                $data["classIco"] .= $responseFromDb["class_ico"];
            }
            array_push($sections, $data);
        }

        \JsonOperations::printJsonFromPhp($sections);
    }

    private function getSectionsFromDbForUser(){
        $usersInstance = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
        $userId = $usersInstance->getUserId();
        return \DataBase::justQueryToDataBase("SELECT * FROM sections INNER JOIN main_rights mrights ON sections.name = mrights.section_text_id AND mrights.user_id = $userId AND mrights.read_right = 1 WHERE available=1");
    }

    public function getAllSections(){
        $sectionsQueryToDbQuery = $this->getSectionsFromDb();
        $sections = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($sectionsQueryToDbQuery)) {
            $data = [];
            $data["id"] = $responseFromDb["id"];
            $translateString = "frontend.mainMenu.";
            $translateString .= $responseFromDb["name"];
            $data["name"] = $this->langs->getMessage($translateString);
            $data["section_text_id"] = $responseFromDb["name"];
            $data["link"] = $responseFromDb["link"];
            if ($responseFromDb["class_ico"]) {
                $data["classIco"] = "fa ";
                $data["classIco"] .= $responseFromDb["class_ico"];
            }
            array_push($sections, $data);
        }
        return $sections;
    }

    public function getSectionsFromDb()
    {
        return \DataBase::justQueryToDataBase("SELECT * FROM sections WHERE available=1");
    }

}
