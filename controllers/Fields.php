<?php

namespace Controller;
class Fields
{
    public $fields;
    private $fieldIdsReq;
    public $groupsWithFields;
    private $structData;
    private $pageIdsFilter;
    private $templateIdOfFields;
    private $filteredTypes;

    public function setFieldsData($id)
    {
        $this->fieldIdsReq = [];
        if ($id) {
            $this->setPagesByTemplateId($id);
            $this->setTemplateIdForPages($id);
            $this->setFilteredTypes();
        }

        $this->structData = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');

        foreach ($this->fields as $keyField => $fieldValue) {
            //группы, у которых id - число, обновляем
            if ((int)$fieldValue['id']) {
                $this->structData->setTablesOntype();
                $this->updateFieldData($fieldValue);
                $this->fieldIdsReq[$fieldValue['id']] = 1;
                //группы, у которых нет id добавляем в таблицу
            } else {
                $this->insertFieldData($fieldValue);
            }
        }

        $this->deleteFields();
    }

    public function setFilteredTypes()
    {
        $this->filteredTypes = $this->getFilteredTypes();
    }

    public function getFilteredTypes()
    {
        $filterTypesFromDb = \DataBase::justQueryToDataBase("SELECT id FROM type_fields WHERE filter= 1");
        $filterTypes = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($filterTypesFromDb)) {
            $filterTypes[$responseFromDb["id"]] = true;
        }

        return $filterTypes;
    }

    public function getTableNameByTypeField($typeField)
    {
        return \DataBase::queryToDataBase("SELECT table_name FROM type_fields WHERE id= $typeField")["table_name"];
    }

    public function setFilterValueForPage($fieldValue, $pageId, $templateId)
    {
        if ($this->isFilteredField($fieldValue["id"])) {
            $this->deleteFilterValueForPage($fieldValue["id"], $pageId, $templateId);
            $fieldTextId = $this->getFieldTextIdById($fieldValue["id"]);

            if (is_array($fieldValue["value"])) {
                foreach ($fieldValue["value"] as $keyField => $fieldValueOfArr) {
                    $this->insertFieldValuesToFilterField($fieldValue["id"], $fieldTextId, $fieldValueOfArr, $pageId, $templateId, $fieldValue['typeId']);
                }
            } else {
                $this->insertFieldValuesToFilterField($fieldValue["id"], $fieldTextId, trim($fieldValue["value"]), $pageId, $templateId, $fieldValue['typeId']);
            }
        }
    }

    public function deleteFieldsByGroupId($groupId)
    {
        $fieldsForGroupsFromDb = \DataBase::justQueryToDataBase("SELECT field_id FROM group_id_field_id WHERE group_id = " . $groupId);

        while ($responseFromDb = \DataBase::responseFromDataBase($fieldsForGroupsFromDb)) // перебор строк таблицы с начала до конца
        {
            if (!$this->fieldIdsReq[$responseFromDb['field_id']] == 1) {
                $this->deleteAllFieldData($responseFromDb['field_id']);
            }
        }
    }

    public function getFieldTypeByFieldId($fieldId)
    {
        return \DataBase::queryToDataBase("SELECT type_id FROM field_id_field_type WHERE id =" . $fieldId)["type_id"];
    }

    public function getIdFieldByTextId($textId)
    {
        $idField = \DataBase::queryToDataBase("SELECT id FROM field_id_name WHERE text_id = '" . $textId . "'");
        return $idField['id'] ? $idField['id'] : null;
    }

    private function setTemplateIdForPages($id)
    {
        $this->templateIdOfFields = $id;
    }

    private function deleteFields()
    {
        foreach ($this->groupsWithFields as $keyGroup => $groupValue) {
            $this->deleteFieldsByGroupId($keyGroup);
        }
    }

    private function deletFieldFromGroup($fieldId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM group_id_field_id WHERE field_id =" . $fieldId);
    }

    private function deleteAllFieldData($fieldId)
    {
        $this->deletFieldFromGroup($fieldId);
        $this->deletFieldReference($fieldId);
        $this->deletFieldNameAndTextID($fieldId);
        $this->deletFieldType($fieldId);
        $this->deletDopPropertiesField($fieldId);
        $this->deletFilterField($fieldId);
    }

    private function deletFilterField($fieldId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM filter_fields WHERE field_id =" . $fieldId);
    }

    private function deletFieldReference($fieldId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM field_id_reference_id WHERE id =" . $fieldId);
    }

    private function deletFieldNameAndTextID($fieldId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM field_id_name WHERE id =" . $fieldId);
    }

    private function deletFieldType($fieldId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM field_id_field_type WHERE id =" . $fieldId);
    }

    private function deletDopPropertiesField($fieldId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM dop_properties_fields WHERE id =" . $fieldId);
    }

    private function updateFieldData($fieldValue)
    {
        if ($fieldValue['id']) {
            if (array_key_exists('name', $fieldValue) && array_key_exists('textId', $fieldValue)) $this->updateFieldNameAndTextId($fieldValue['id'], $fieldValue['name'], $fieldValue['textId']);
            if (array_key_exists('typeId', $fieldValue)) {
                $this->updateFieldTypeId($fieldValue['id'], $fieldValue['typeId']);
                if ($fieldValue['typeId'] == 4 || $fieldValue['typeId'] == 5 || $fieldValue['typeId'] == 12) {
                    if ($fieldValue['referenceId'] != 0 && $fieldValue['referenceId'] != '') $this->setReference($fieldValue['id'], $fieldValue['referenceId']);
                    //удаляем справочники для данного поля, если поле не выпадающий список
                } else {
                    $this->deleteReferenceForField($fieldValue['id']);
                }
            }
            if (array_key_exists('necessarily', $fieldValue) && array_key_exists('noIndex', $fieldValue)) $this->updateFieldHintAndNecessarily($fieldValue['id'], $fieldValue['hint'], $fieldValue['necessarily'], $fieldValue['noIndex'], $fieldValue['filtered']);
            if (array_key_exists('parentTextId', $fieldValue)) $this->updateParentGroup($fieldValue['id'], $fieldValue['parentTextId'], $fieldValue['num']);
            if ($this->filteredTypes[$fieldValue['typeId']]) $this->updateValueToFilterTableByType($fieldValue);
        }
    }

    private function updateFieldNameAndTextId($id, $name, $textId)
    {
        \DataBase::justQueryToDataBase("UPDATE field_id_name SET name = '" . $name . "', text_id='" . $textId . "' WHERE id = " . $id);
    }

    private function updateFieldTypeId($id, $typeId)
    {
        \DataBase::justQueryToDataBase("UPDATE field_id_field_type SET type_id = '" . $typeId . "' WHERE id = " . $id);
    }

    private function updateFieldHintAndNecessarily($id, $hint, $necessarily, $noindex, $filtered)
    {
        \DataBase::justQueryToDataBase("UPDATE dop_properties_fields SET indexed=" . $noindex . ", filtered=" . $filtered . ", necessarily=" . $necessarily . ", hint='" . $hint . "'  WHERE id = " . $id);
    }

    private function updateValueToFilterTableByType($fieldValue)
    {
        if ($fieldValue['filtered']) {
            $tableName = $this->getValueToFilterTableByType($fieldValue['typeId']);
            $this->setValueFieldOnPageIds($tableName, $fieldValue['textId'], $fieldValue['typeId']);
        } else {
            $this->deleteValueFromFilterTableByType($fieldValue['id']);
        }
    }

    private function getValueToFilterTableByType($typeId)
    {
        return $this->structData->tablesOnType[$typeId];
    }

    private function setValueFieldOnPageIds($tableName, $fieldTextId, $fieldTypeId)
    {
        $sqlString = "SELECT tbl_val.value, tbl_val.page_id, tbl_val.field_id FROM " . $tableName . " as tbl_val INNER JOIN field_id_name fin ON tbl_val.field_id = fin.id AND fin.text_id = '" . $fieldTextId . "' WHERE type=1";
        $valueFieldsReq = \DataBase::justQueryToDataBase($sqlString);
        while ($responseFromDb = \DataBase::responseFromDataBase($valueFieldsReq)) {
            $this->deleteFilterValueForPage($responseFromDb["field_id"], $responseFromDb["page_id"]);
            $this->insertFieldValuesToFilterField($responseFromDb["field_id"], $fieldTextId, $responseFromDb["value"], $responseFromDb["page_id"], $this->templateIdOfFields, $fieldTypeId);
        }
    }

    private function getPagesByTemplateId($id)
    {
        $pageIds = [];
        $parentPagesReqToDb = \DataBase::justQueryToDataBase("SELECT DISTINCT page_id FROM page_id_to_template_id WHERE template_id = " . $id);
        while ($responseFromDb = \DataBase::responseFromDataBase($parentPagesReqToDb)) {
            array_push($pageIds, $responseFromDb["page_id"]);
        }
        return $pageIds;
    }

    private function setPagesByTemplateId($id)
    {
        $this->pageIdsFilter = $this->getPagesByTemplateId($id);
    }

    private function deleteValueFromFilterTableByType($id)
    {
        \DataBase::justQueryToDataBase("DELETE FROM filter_fields WHERE field_id =" . $id);
    }

    private function updateParentGroup($id, $parentTextId, $num)
    {
        $idParentGroup = $this->getGroupIdByTextId($parentTextId);
        if ($idParentGroup) \DataBase::justQueryToDataBase("UPDATE group_id_field_id SET group_id = '" . $idParentGroup . "', sort_num=$num WHERE field_id = " . $id);
    }

    private function getGroupIdByTextId($textId)
    {
        $idParentGroup = \DataBase::queryToDataBase("SELECT id FROM group_id_name WHERE text_id = '" . $textId . "'");
        if ($idParentGroup['id']) {
            return $idParentGroup['id'];
        } else {
            return false;
        }
    }

    private function setReference($id, $referenceId)
    {
        if ($this->isHaveReferenceForField($id)) {
            $this->updateReferenceForField($id, $referenceId);
        } else {
            $this->createReferenceForField($id, $referenceId);
        }
    }

    private function updateReferenceForField($id, $referenceId)
    {
        \DataBase::justQueryToDataBase("UPDATE field_id_reference_id SET reference_id='" . $referenceId . "' WHERE field_id = '" . $id . "'");
    }

    private function createReferenceForField($id, $referenceId)
    {
        \DataBase::justQueryToDataBase("INSERT field_id_reference_id SET field_id=" . $id . ", reference_id = " . $referenceId);
    }

    private function deleteReferenceForField($id)
    {
        \DataBase::justQueryToDataBase("DELETE FROM field_id_reference_id WHERE field_id = " . $id);
    }

    private function isHaveReferenceForField($id)
    {
        $selectReference = \DataBase::queryToDataBase("SELECT reference_id FROM field_id_reference_id WHERE field_id = '" . $id . "'");
        return !!$selectReference['reference_id'];
    }

    private function insertFieldData($fieldValue)
    {
        if (array_key_exists('name', $fieldValue) && array_key_exists('textId', $fieldValue)) {
            $this->insertNewField($fieldValue['name'], $fieldValue['textId']);
            $idNewField = $this->getIdFieldByTextId($fieldValue['textId']);
            $this->fieldIdsReq[$idNewField] = 1;
            if (array_key_exists('necessarily', $fieldValue) && array_key_exists('noIndex', $fieldValue)) $this->createFieldHintAndNecessarily($idNewField, $fieldValue['hint'], $fieldValue['necessarily'], $fieldValue['noIndex'], $fieldValue['filtered']);
            if (array_key_exists('parentTextId', $fieldValue)) $this->createParentGroup($idNewField, $fieldValue['parentTextId'], $fieldValue['num']);
            if (array_key_exists('typeId', $fieldValue)) {
                $this->createFieldTypeId($idNewField, $fieldValue['typeId']);
                if ($fieldValue['typeId'] == 4 || $fieldValue['typeId'] == 5 || $fieldValue['typeId'] == 12) {
                    $this->setReference($idNewField, $fieldValue['referenceId']);
                    //удаляем справочники для данного поля, если поле не выпадающий список
                } else {
                    $this->deleteReferenceForField($fieldValue['id']);
                }
            }
        }
    }

    private function createFieldTypeId($id, $typeId)
    {
        \DataBase::justQueryToDataBase("INSERT field_id_field_type SET type_id = '" . $typeId . "', id = " . $id);
    }

    private function createParentGroup($id, $parentTextId, $num)
    {
        $idParentGroup = $this->getGroupIdByTextId($parentTextId);
        if ($idParentGroup && !$this->isHasGroupAndFieldPair($idParentGroup, $id)) \DataBase::justQueryToDataBase("INSERT group_id_field_id SET group_id = '" . $idParentGroup . "', field_id = " . $id . ", sort_num=$num");
    }

    private function isHasGroupAndFieldPair($groupId, $fieldId)
    {
        return !!\DataBase::queryToDataBase("SELECT field_id FROM group_id_field_id WHERE group_id = $groupId AND field_id= $fieldId")["field_id"];
    }

    private function createFieldHintAndNecessarily($id, $hint, $necessarily, $no_index, $filtered)
    {
        \DataBase::justQueryToDataBase("INSERT dop_properties_fields SET hint = '" . $hint . "', necessarily=" . $necessarily . ", indexed=" . $no_index . ", filtered=" . $filtered . ", id = " . $id);
    }

    private function insertNewField($name, $textId)
    {
        \DataBase::justQueryToDataBase("INSERT field_id_name SET name ='" . $name . "', text_id = '" . $textId . "'");
    }

    private function getFieldTextIdById($id)
    {
        $fieldName = \DataBase::queryToDataBase("SELECT text_id FROM field_id_name WHERE id = " . $id)["text_id"];
        return $fieldName;
    }

    private function deleteFilterValueForPage($fieldId, $pageId, $templateId = null)
    {
        \DataBase::justQueryToDataBase("DELETE FROM filter_fields WHERE page_id= " . $pageId . " AND field_id= " . $fieldId);
    }

    private function insertFieldValuesToFilterField($fieldId, $fieldTextId, $fieldValue, $pageId, $templateId, $fieldType)
    {
        $trimmedValue = trim($fieldValue);
        if ($fieldType == 1 || $fieldType == 8) {
            $this->insertFieldValuesToFilterFieldAsString($fieldId, $fieldTextId, $trimmedValue, $pageId, $templateId);
        } else if($fieldType == 9) {
			$dateValue = $trimmedValue ? $trimmedValue : 'NULL';
			$this->insertFieldValuesToFilterFieldAsDate($fieldId, $fieldTextId, $dateValue, $pageId, $templateId);
		}else {
			$numberValue = is_numeric($trimmedValue) ? '"'.$trimmedValue."'" : 'NULL';
            $this->insertFieldValuesToFilterFieldAsNumber($fieldId, $fieldTextId, $numberValue, $pageId, $templateId);
        }
    }

    private function insertFieldValuesToFilterFieldAsNumber($fieldId, $fieldTextId, $fieldValue, $pageId, $templateId)
    {	
		\DataBase::justQueryToDataBase("INSERT filter_fields SET page_id = " . $pageId . ", field_id=" . $fieldId . ", field_value=" . $fieldValue . ", template_id=" . $templateId . ", field_name='" . $fieldTextId . "'");
    }

    private function insertFieldValuesToFilterFieldAsString($fieldId, $fieldTextId, $fieldValue, $pageId, $templateId)
    {
        \DataBase::justQueryToDataBase("INSERT filter_fields SET page_id = " . $pageId . ", field_id=" . $fieldId . ", field_value_string='" . $fieldValue . "', template_id=" . $templateId . ", field_name='" . $fieldTextId . "'");
    }
	
	private function insertFieldValuesToFilterFieldAsDate($fieldId, $fieldTextId, $fieldValue, $pageId, $templateId)
    {
        \DataBase::justQueryToDataBase("INSERT filter_fields SET page_id = " . $pageId . ", field_id=" . $fieldId . ", field_value_date=" . $fieldValue . ", template_id=" . $templateId . ", field_name='" . $fieldTextId . "'");
    }


    private function isFilteredField($id)
    {
        return \DataBase::queryToDataBase("SELECT filtered FROM dop_properties_fields WHERE id= " . $id)["filtered"];
    }
}
