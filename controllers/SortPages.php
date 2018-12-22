<?php

namespace Controller;
class SortPages
{

    private $arrOfPagesBySort;
    private $countRows;

    public function changeSortOrParentOfPage($data)
    {
        $this->getAllChildrenPageSorted($data);
        $this->addSortPage($data);
        $this->setSortValueForPages();
        $this->changeParentId($data["page_id"], $data["parent_id"]);
        $this->setCountSortNumToAllApges();
        $this->updatePagesUri($data["page_id"]);
        \Response::goodResponse();
    }

    private function getAllChildrenPageSorted($data)
    {
        $reqToDb = \DataBase::justQueryToDataBase("SELECT id FROM page_parent_id WHERE parent_id = " . $data["parent_id"] . " ORDER BY sort ASC");

        $this->arrOfPagesBySort = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($reqToDb)) {
            if ($responseFromDb["id"] != $data["page_id"]) {
                array_push($this->arrOfPagesBySort, $responseFromDb["id"]);
            }
        }
    }

    private function addSortPage($data)
    {
        array_splice($this->arrOfPagesBySort, $data["position"], 0, $data["page_id"]);
    }

    private function setSortValueForPages()
    {
        foreach ($this->arrOfPagesBySort as $key => $value) {
            \DataBase::justQueryToDataBase("UPDATE page_parent_id SET sort=" . $key . " WHERE id = " . $value);
        }
    }

    private function changeParentId($id, $parent_id)
    {
        \DataBase::justQueryToDataBase("UPDATE page_parent_id SET parent_id = " . $parent_id . " WHERE id = " . $id);
    }

    private function updatePagesUri($id)
    {
        $structDataController = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $pageUri = $structDataController->getUriPage($id);
        $structDataController->structId = $id;
        $structDataController->setterUriAndChildrenPath($pageUri);
    }

    public function setCountSortNumToAllApges()
    {
        $countRows = \DataBase::queryToDataBase("SELECT COUNT(*) from page_parent_id")["COUNT(*)"];
        $this->countRows = $countRows;
        $this->setCountSortNumToPagesByCount(0);
    }

    private function setCountSortNumToPagesByCount($parentId)
    {

        $pages = \DataBase::justQueryToDataBase("SELECT id, sort from page_parent_id WHERE parent_id=" . $parentId . " ORDER BY sort");

        while ($responseFromDb = \DataBase::responseFromDataBase($pages)) {
            $this->countRows++;
            \DataBase::justQueryToDataBase("UPDATE page_parent_id SET countSortNum = " . $this->countRows . " WHERE id = " . $responseFromDb["id"]);
            $this->setCountSortNumToPagesByCount($responseFromDb["id"]);
        }
    }
}
