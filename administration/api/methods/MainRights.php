<?php

namespace Api;
class MainRights
{
    private $sectionsController;
    private $users;

    function __construct()
    {
        $this->sectionsController = \ClassesOperations::autoLoadClass('\Controller\Sections', '/controllers/Sections.php');
        $this->users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
    }

    public function set($id)
    {
        $this->users->setUserRights($id, $_POST['data']);
        \Response::goodResponse();
    }

    public function get($id)
    {
        $result = [];
        $userName = $this->users->getUserName($id);
        $result['name'] = $userName;
        $result['id'] = $id;
        $userRights = $this->users->getUserMainRights($id);
        $allSections = $this->sectionsController->getAllSections();
        $allSectionsWithRights = [];
        foreach($allSections as $value){
            $sectionName = $value['section_text_id'];
            $data = $value;
            if($userRights[$sectionName]){
                $data['accesses'] = $userRights[$sectionName];
            }else{
                $data['accesses'] = $this->getDefaultAccesses();
            }
            array_push($allSectionsWithRights, $data);
        }
        $result['main_rights'] = $allSectionsWithRights;
        return \JsonOperations::printJsonFromPhp($result);
    }

    private function getDefaultAccesses(){
        $accesses = [];
        $accesses['read_right'] = 0;
        $accesses['create_right'] = 0;
        $accesses['edit_right'] = 0;
        $accesses['delete_right'] = 0;
        return $accesses;
    }
}