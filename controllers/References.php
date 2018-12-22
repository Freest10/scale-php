<?php

namespace Controller;
class References
{
    public $elementData = [];
    public $references;
    private $referenceDatas;
    private $referenceTemplateId;
    private $allElementsOfReference;
    private $updatedElementsOrReference;
    private $struct_data;
    private $clientOptions;
    private $templatesType;

    function __construct()
    {
        $this->templatesType = \ClassesOperations::autoLoadClass('\Controller\TemplatesType', '/controllers/TemplatesType.php');
    }

    public function getReferenceLimit($begin, $limit)
    {
        $dataToJsonLimit = [
            'selectResponse' => 'SELECT * FROM `template_parent_id` WHERE parent_id = 2',
            'countResponse' => 'template_parent_id WHERE parent_id = 2',
            'templatesData' => [
                'id' => true
            ],
            'begin' => $begin,
            'limit' => $limit,
            'type' => 'reference'
        ];

        \JsonOperations::createLimitJson($dataToJsonLimit);
    }

    public function getReferenceDatas($id)
    {
        $this->referenceDatas = [];
        $this->referenceDatas['name'] = TemplatesType::getTemplateName($id);
        $this->referenceTemplateId = $id;
        $this->getMainDatasOfReference();
        \JsonOperations::printJsonFromPhp($this->referenceDatas);
    }

    public function getReferenceDataFieldValue($fieldValueId, $fieldId)
    {
        return \DataBase::queryToDataBase("SELECT value FROM field_values_type_string WHERE type=2 AND page_id = " . $fieldValueId . " AND field_id=" . $fieldId)["value"];
    }

    public function getReferenceDataValue($refId, $refDataId)
    {
        return \DataBase::queryToDataBase("SELECT name FROM reference_data WHERE reference_id = " . $refId . " AND reference_data_id=" . $refDataId)["name"];
    }

    public function getReferenceMultiDataValue($refId, $refDataIds)
    {
        if ($refDataIds) {
            $refDataArr = explode(",", $refDataIds);
            $resultValue = [];
            foreach ($refDataArr as $key => $value) {
                $result = $this->getReferenceDataValue($refId, $value);
                array_push($resultValue, $result);
            }
            $resultValue = join(",", $resultValue);
            return $resultValue;
        }
    }

    public function getReferenceDatasForAddress($id)
    {
        $this->referenceDatas = [];
        $this->referenceTemplateId = $id;
        $this->getMainDatasOfReferenceForAddress();
        \JsonOperations::printJsonFromPhp($this->referenceDatas);
    }


    private function getMainDatasOfReference()
    {
        $referenceDatasForReferenceTemplateDB = \DataBase::justQueryToDataBase("SELECT reference_data_id, name FROM reference_data WHERE reference_id = " . $this->referenceTemplateId);

        while ($responseFromDb = \DataBase::responseFromDataBase($referenceDatasForReferenceTemplateDB)) // перебор строк таблицы с начала до конца
        {
            if (!is_array($this->referenceDatas["items"])) $this->referenceDatas["items"] = [];
            $referenceDataNum = count($this->referenceDatas["items"]);
            $this->referenceDatas["items"][$referenceDataNum] = [];
            $this->referenceDatas["items"][$referenceDataNum]['name'] = $responseFromDb['name'];
            $this->referenceDatas["items"][$referenceDataNum]['id'] = $responseFromDb['reference_data_id'];
        }
    }

    private function getMainDatasOfReferenceForAddress()
    {
        $referenceDatasForReferenceTemplateDB = \DataBase::justQueryToDataBase("SELECT refdata.reference_data_id AS 'reference_data_id', refdata.name AS 'name', fvts.value AS 'addresses' FROM reference_data refdata INNER JOIN field_values_type_string fvts ON refdata.reference_data_id = fvts.page_id AND type=2 WHERE refdata.reference_id = " . $this->referenceTemplateId);

        while ($responseFromDb = \DataBase::responseFromDataBase($referenceDatasForReferenceTemplateDB)) // перебор строк таблицы с начала до конца
        {
            if (!is_array($this->referenceDatas["items"])) $this->referenceDatas["items"] = [];
            $referenceDataNum = count($this->referenceDatas["items"]);
            $this->referenceDatas["items"][$referenceDataNum] = [];
            $this->referenceDatas["items"][$referenceDataNum]['name'] = $responseFromDb['name'];
            $this->referenceDatas["items"][$referenceDataNum]['id'] = $responseFromDb['reference_data_id'];
            $this->referenceDatas["items"][$referenceDataNum]['addresses'] = $responseFromDb['addresses'];
        }
    }

    public function setReferenceDatas($id)
    {
        $this->referenceTemplateId = $id;
        $this->templatesType->updateTemplateName($id, $_POST['name']);
        $this->allElementsForReference();
        $this->setReferenceDatasName($_POST['items']);
        $this->compareUpdatedWithAllElementsOfReferenceToDelete();
        \Response::goodResponse();
    }

    public function setReferenceDatasForAddresses($id)
    {
        $this->referenceTemplateId = $id;
        $this->allElementsForReference();
        $this->setReferenceDatasNameForAddress($_POST['data']);
        $this->compareUpdatedWithAllElementsOfReferenceToDeleteAddress();
        \Response::goodResponse();
    }

    private function allElementsForReference()
    {
        $allElementsForReference = \DataBase::justQueryToDataBase("SELECT reference_data_id FROM reference_data WHERE reference_id = " . $this->referenceTemplateId);
        while ($responseFromDb = \DataBase::responseFromDataBase($allElementsForReference)) // перебор строк таблицы с начала до конца
        {
            if (!is_array($this->allElementsOfReference)) $this->allElementsOfReference = [];
            $referenceDataNum = count($this->allElementsOfReference);
            $this->allElementsOfReference[$referenceDataNum] = $responseFromDb['reference_data_id'];
        }
    }

    private function setReferenceDatasName($referenceDatas)
    {
        foreach ($referenceDatas as $value) {
            if (is_numeric($value['id'])) {
                if (!is_array($this->updatedElementsOrReference)) $this->updatedElementsOrReference = [];
                $this->updatedElementsOrReference[$value['id']] = 1;
                $this->updateReferenceDataName($value['id'], $value['name']);
            } else {
                $this->setReferenceDataName($value['name']);
            }
        }
    }

    private function setReferenceDatasNameForAddress($referenceDatas)
    {
        foreach ($referenceDatas as $value) {
            if (is_numeric($value['id'])) {
                if (!is_array($this->updatedElementsOrReference)) $this->updatedElementsOrReference = [];
                $this->updatedElementsOrReference[$value['id']] = 1;
                $this->updateReferenceDataName($value['id'], $value['name']);
                $this->updateReferenceAddress($value['id'], $value['addresses']);
            } else {
                $this->setReferenceDataName($value['name']);
                $this->createReferenceAddress($this->getLasCreatedReferenceDataId(), $value['addresses']);
            }
        }
    }

    private function getLasCreatedReferenceDataId()
    {
        return \DataBase::queryToDataBase("select max(reference_data_id) from reference_data")["max(reference_data_id)"];
    }

    private function updateReferenceAddress($pageId, $value)
    {
        \DataBase::justQueryToDataBase("UPDATE field_values_type_string SET value = '" . $value . "' WHERE type=2 AND page_id = " . $pageId);
    }

    private function createReferenceAddress($pageId, $value)
    {
        \DataBase::justQueryToDataBase("INSERT field_values_type_string SET value = '" . $value . "', type=2, field_id=76, page_id = " . $pageId);
    }

    private function compareUpdatedWithAllElementsOfReferenceToDelete()
    {
        foreach ($this->allElementsOfReference as $value) {
            if (!$this->updatedElementsOrReference[$value]) {
                $this->deleteRefernceElement($value);
            }
        }
    }

    private function compareUpdatedWithAllElementsOfReferenceToDeleteAddress()
    {
        foreach ($this->allElementsOfReference as $value) {
            if (!$this->updatedElementsOrReference[$value]) {
                $this->deleteRefValueAddress($value);
                $this->deleteRefernceElement($value);
            }
        }
    }

    private function deleteRefValueAddress($pageId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM field_values_type_string WHERE page_id =" . $pageId);
    }


    private function deleteRefernceElement($id)
    {
        \DataBase::queryToDataBase("DELETE FROM reference_data WHERE reference_data_id =" . $id);
    }

    private function updateReferenceDataName($id, $name)
    {
        \DataBase::queryToDataBase("UPDATE reference_data SET name = '" . $name . "' WHERE reference_data_id = " . $id);
    }

    private function setReferenceDataName($name)
    {
        \DataBase::justQueryToDataBase("INSERT INTO reference_data SET name='" . $name . "', reference_id=" . $this->referenceTemplateId);
    }

    public function getReferenceElementData($id)
    {
        $this->connectStructData();
        $refElemTemplateAndName = $this->getReferenceTemplateAndNameForElement($id);
        $this->referenceTemplateId = $refElemTemplateAndName['reference_id'];
        $this->elementData['name'] = $refElemTemplateAndName['name'];
        $this->struct_data->structId = $id;
        $this->struct_data->structTemplateId = $this->referenceTemplateId;
        $this->struct_data->structTypeId = 2;
        $this->struct_data->generateStructData();
        $this->elementData['groups'] = $this->struct_data->structData['groups'];
        if (is_array($this->struct_data->structData['fields'])) $this->elementData['fields'] = $this->struct_data->structData['fields'];
        \JsonOperations::printJsonFromPhp($this->elementData);
    }

    private function getReferenceTemplateAndNameForElement($id)
    {
        $referenceTemplateFromDbQuery = \DataBase::queryToDataBase("SELECT reference_id, name FROM reference_data WHERE reference_data_id = " . $id);
        return $referenceTemplateFromDbQuery;
    }

    public function getReferenceDataNameById($id)
    {
        $referenceDataNameFromDbQuery = \DataBase::queryToDataBase("SELECT name FROM reference_data WHERE reference_data_id = " . $id);
        return $referenceDataNameFromDbQuery['name'];
    }

    public function setReferenceElementData($id)
    {
        $this->connectStructData();
        $this->id = $id;
        $this->updateReferenceDataName($id, $this->elementData['name']);
        $this->struct_data->structId = $this->id;
        $this->struct_data->structTypeId = 2;
        $this->struct_data->setFieldsData($this->elementData);
        \Response::goodResponse();
    }

    public function getReferencesDataForClient($options)
    {
        $ids = $options["ids"];
        $this->clientOptions = $options;
        $idsToSql = "";
        if (count($ids) > 0) {
            foreach ($ids as $key => $value) {
                if ($key != 0) {
                    $idsToSql .= " OR";
                }
                $idsToSql .= " tin.id = " . $value;
            }
        }

        $queryToDbQuery = \DataBase::justQueryToDataBase("
				SELECT tin.id as id, tin.name as name
				FROM template_id_name tin
				WHERE " . $idsToSql
        );
        $this->references = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($queryToDbQuery)) {
            $data = [];
            $dataId = $responseFromDb["id"];
            $data["text"] = $responseFromDb["name"];
            $data["items"] = $this->getElementsOfRefernceClient($responseFromDb["id"]);
            $this->references[$dataId] = $data;
        }
        return $this->references;
    }

    private function getElementsOfRefernceClient($id)
    {
        $props = $this->clientOptions["props"];
        $groups = $this->clientOptions["groups"];
        $queryToDbQuery = \DataBase::justQueryToDataBase("
				SELECT rfd.reference_data_id as id, rfd.name as name
				FROM reference_data rfd
				WHERE rfd.reference_id=" . $id
        );
        $items = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($queryToDbQuery)) {
            $data = [];
            $dataId = $responseFromDb["id"];
            $data["text"] = $responseFromDb["name"];

            if (count($props) > 0) {
                if (!$this->struct_data) {
                    $this->connectStructData();
                }
                $this->struct_data->structTemplateId = $id;
                $this->struct_data->structId = $responseFromDb["id"];
                $this->struct_data->structTypeId = 2;
                $data["props"] = $this->struct_data->getFieldsByTypeIdForClient($props, true);
            }

            if (count($groups) > 0) {
                if (!$this->struct_data) {
                    $this->connectStructData();
                }
                $this->struct_data->structTemplateId = $id;
                $this->struct_data->structId = $responseFromDb["id"];
                $this->struct_data->structTypeId = 2;
                $data["groups"] = $this->struct_data->getGroupsByTypeIdForClient($groups, true);
            }

            $items[$dataId] = $data;
        }
        return $items;
    }

    private function connectStructData()
    {
        require CURRENT_WORKING_DIR . '/controllers/StructData.php';
        $this->struct_data = new \Controller\StructData();
    }
}