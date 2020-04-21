<?php

namespace Controller;

class StructData
{

    public $structId;
    public $structTemplateId;
    public $structData;
    public $structTypeId;
    public $tablesOnType;
    private $fields;
    private $langs;
    private $generalFields = [
        'url' => -2,
        'active' => -3,
        'h1' => -4,
        'title' => -5,
        'description' => -6,
        'noIndex' => -8
    ];

    function __construct()
    {
        $this->langs = \Langs::getInstance();
        $this->fields = \ClassesOperations::autoLoadClass('\Controller\Fields', '/controllers/Fields.php');
        $this->fields->setFilteredTypes();
    }

    public function generateStructData()
    {
        $this->getStructGroups($this->structTemplateId);
    }

    public function getStructFieldsForTemplate($id)
    {
        $groupsToDbQuery = \DataBase::justQueryToDataBase("SELECT gin.id FROM template_id_group_id tig INNER JOIN group_id_name gin ON tig.group_id = gin.id WHERE tig.template_id = " . $id);
        $this->setTablesOntype();
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $this->getFieldsForGroup($responseFromDb['id']);
        }
    }

    public function createGeneralGroup()
    {
        $generalGroup = [];
        $generalGroup["name"] = $this->langs->getMessage("backend.fields.share");
        $generalGroup["textId"] = "general_fields";
        $generalGroup["id"] = 0;
        return $generalGroup;
    }

    public function updateGeneralFieldsById($pageId, $fieldTextId, $value)
    {
        if ($pageId && array_key_exists($fieldTextId, $this->generalFields)) {
            $this->structId = $pageId;
            $fieldValue = [];
            $fieldValue['id'] = $this->generalFields[$fieldTextId];
            $fieldValue['value'] = $value;
            $this->updateGeneralField($fieldValue);
        }
    }

    public function getFieldValue($field_id, $field_type, $page_id)
    {
        $this->setTablesOntype();
        if ($field_type == 14) {
            $valueField = $this->selectFieldValuesLinkPages($page_id, $field_id, $this->tablesOnType[$field_type]);
        } else if ($field_type == 12) {
            $valueField = $this->selectFieldSostavValues($page_id, $field_id, $this->tablesOnType[$field_type]);
        } else if ($field_type == 15) {
            $valueField = '';
        } else if ($field_type == 5) {
            $valueField = $this->selectFieldValues($page_id, $field_id, $this->tablesOnType[$field_type]);
        } else {
            $valueField = $this->selectFieldValue($page_id, $field_id, $this->tablesOnType[$field_type]);
        }

        return $valueField;
    }

    public function setFieldValueByPageId($pageId, $fieldTextId, $value)
    {
        $fieldId = $this->fields->getIdFieldByTextId($fieldTextId);
        $typeId = $this->fields->getFieldTypeByFieldId($fieldId);
        $this->structId = $pageId;
        $this->structTypeId = 1;
        $this->setTablesOntype();
        $fieldValue = [];
        $fieldValue['id'] = $fieldId;
        $fieldValue["typeId"] = $typeId;
        $fieldValue["value"] = $value;
        $this->updateFieldByType($fieldValue);
        $page = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
        $templateId = $page::getTypePageById($pageId);
        $this->fields->setFilterValueForPage($fieldValue, $pageId, $templateId);
    }

    public function getGroupsByPageIdForClient($groups)
    {
        $groupsName = "";
        if (count($groups) > 0) {
            $groupsName .= "AND (";
            foreach ($groups as $key => $value) {
                if ($key != 0) {
                    $groupsName .= " OR";
                }
                $groupsName .= " gin.text_id = '" . $value . "'";
            }
            $groupsName .= ")";
        }

        $groupsToDbQuery = \DataBase::justQueryToDataBase("
				SELECT gin.id as id, gin.name as name, gin.text_id as text_id
				FROM template_id_group_id tig
				INNER JOIN group_id_name gin
				ON tig.group_id = gin.id " . $groupsName . "
				WHERE tig.template_id = " . $this->structTemplateId
        );
        $groups = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $data = [];
            $dataName = $responseFromDb["text_id"];
            $data = [];
            $data["id"] = $responseFromDb["id"];
            $data["text"] = $responseFromDb["name"];
            $data["props"] = $this->getPropsForGroup($responseFromDb["id"]);
            $groups[$dataName] = $data;
        }

        return $groups;
    }

    public function setFieldsData($pageData)
    {
        $this->structData = $pageData;
        $this->setTablesOntype();
        foreach ($this->structData['fields'] as $keyField => $fieldValue) {
            if ($fieldValue['id'] > 0) {
                $this->updateFieldByType($fieldValue);
                $this->fields->setFilterValueForPage($fieldValue, $this->structId, $this->structTemplateId);
            } else {
                $this->updateGeneralField($fieldValue);
            }
        }
    }

    public function getPropsForGroup($group_id)
    {
        $propsToDbQuery = \DataBase::justQueryToDataBase("
				SELECT gif.field_id as id, fif.type_id as type_id, fin.name as name, fin.text_id as text_id
				FROM group_id_field_id gif
				INNER JOIN field_id_field_type fif
				ON gif.field_id = fif.id
				INNER JOIN field_id_name fin
				ON fif.id = fin.id
				WHERE gif.group_id = " . $group_id . "
				ORDER BY sort_num
				"
        );
        $props = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($propsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $dataId = $responseFromDb["id"];
            $dataTypeId = $responseFromDb["type_id"];
            $dataValue = $this->getFieldValue($dataId, $dataTypeId, $this->structId);
            if ($dataValue != "" || ($dataTypeId == 10 && $dataValue != "00:00:00")) {
                $data = [];
                $dataName = $responseFromDb["text_id"];
                $data["id"] = $responseFromDb["id"];
                $data["text"] = $responseFromDb["name"];
                $data["value"] = $dataValue;
                $data["typeId"] = $responseFromDb["type_id"];
                if ($data["typeId"] == 4) {
                    $data["referenceId"] = $this->getReferenceIdByFieldId($data["id"]);
                }
                $props[$dataName] = $data;
            }
        }
        return $props;
    }

    public function setterUriAndChildrenPath($uriValue)
    {
        //если главная страница
        if ($this->isMainPageId($this->structId)) {
            $this->changeOtherMainPage();
            $main = true;
        }

        $this->setUriPage($uriValue);
        $path = $this->getUriPage($this->structId);
        $full_path = $this->updateFullPathUri($path, $this->structId, $main);
        $this->updateFullUriChildrenPages($this->structId, $full_path);
    }

    public function getPropsForGroupJustType($group_id, $withValue)
    {
        $propsToDbQuery = \DataBase::justQueryToDataBase("
				SELECT gif.field_id as id, fif.type_id as type_id, fin.name as name, fin.text_id as text_id, dpf.necessarily as necessarily, dpf.indexed as indexed, dpf.filtered as filtered
				FROM group_id_field_id gif
				INNER JOIN field_id_field_type fif
				ON gif.field_id = fif.id
				INNER JOIN field_id_name fin
				ON fif.id = fin.id
				INNER JOIN dop_properties_fields dpf
                ON gif.field_id = dpf.id
				WHERE gif.group_id = " . $group_id . "
				ORDER BY sort_num
				"
        );
        $props = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($propsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $dataName = $responseFromDb["text_id"];
            $data = [];
            $data["id"] = $responseFromDb["id"];
            $data["text"] = $responseFromDb["name"];
            $data["typeId"] = $responseFromDb["type_id"];
            $data["necessarily"] = !!$responseFromDb["necessarily"];
            $data["indexed"] = !!$responseFromDb["indexed"];
            $data["filtered"] = !!$responseFromDb["filtered"];
            if ($data["typeId"] == 4) {
                $data["referenceId"] = $this->getReferenceIdByFieldId($data["id"]);
            }

            if ($withValue) {
                $data["value"] = $this->getFieldValue($responseFromDb["id"], $responseFromDb["type_id"], $this->structId);
            }

            $props[$dataName] = $data;
        }

        return $props;
    }

    public function getGroupsByTypeIdForClient($groups, $withValue = false)
    {
        $groupsToSql = "";
        if (count($groups) > 0) {
            $groupsToSql .= "AND (";
            foreach ($groups as $key => $value) {
                if ($key != 0) {
                    $groupsToSql .= " OR";
                }
                $groupsToSql .= " gin.text_id = '" . $value . "'";
            }
            $groupsToSql .= ")";
        }

        $groupsTypesQueryToDbQuery = \DataBase::justQueryToDataBase("
				SELECT tig.group_id as id, gin.name as name, gin.text_id as text_id
				FROM template_id_group_id tig
				INNER JOIN group_id_name gin
				ON tig.group_id = gin.id " . $groupsToSql . "
				WHERE tig.template_id=" . $this->structTemplateId
        );
        $groupsData = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsTypesQueryToDbQuery)) {
            $data = [];
            $dataName = $responseFromDb["text_id"];
            $data["text"] = $responseFromDb["name"];
            $data["id"] = $responseFromDb["id"];
            $data["props"] = $this->getPropsForGroupJustType($responseFromDb["id"], $withValue = false);
            $groupsData[$dataName] = $data;
        }

        return $groupsData;
    }

    public function getFieldsByTypeIdForClient($props, $withValue = false)
    {
        $propsToSql = "";
        if (count($props) > 0) {
            $propsToSql .= "AND (";
            foreach ($props as $key => $value) {
                if ($key != 0) {
                    $propsToSql .= " OR";
                }
                $propsToSql .= " fin.text_id = '" . $value . "'";
            }
            $propsToSql .= ")";
        }

        $propsTypesQueryToDbQuery = \DataBase::justQueryToDataBase("
				SELECT fin.id as id, fin.name as name, fin.text_id as text_id, fift.type_id as type_id, dpf.necessarily as necessarily, dpf.indexed as indexed, dpf.filtered as filtered
				FROM template_id_group_id tig
				INNER JOIN group_id_field_id gif
				ON tig.group_id = gif.group_id
				INNER JOIN field_id_name fin
				ON gif.field_id = fin.id " . $propsToSql . "
				INNER JOIN field_id_field_type fift
				ON gif.field_id = fift.id
				INNER JOIN dop_properties_fields dpf
                ON gif.field_id = dpf.id
				WHERE tig.template_id=" . $this->structTemplateId
        );
        $propsData = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($propsTypesQueryToDbQuery)) {
            $data = [];
            $dataName = $responseFromDb["text_id"];
            $data["text"] = $responseFromDb["name"];
            $data["id"] = $responseFromDb["id"];
            $data["typeId"] = $responseFromDb["type_id"];
            $data["necessarily"] = !!$responseFromDb["necessarily"];
            $data["indexed"] = !!$responseFromDb["indexed"];
            $data["filtered"] = !!$responseFromDb["filtered"];
            if ($data["typeId"] == 4) {
                $data["referenceId"] = $this->getReferenceIdByFieldId($data["id"]);
            }

            if ($withValue) {
                $data["value"] = $this->getFieldValue($responseFromDb["id"], $responseFromDb["type_id"], $this->structId);
            }

            $propsData[$dataName] = $data;
        }

        return $propsData;
    }

    public function getFieldTextValueById($id)
    {
        return \DataBase::queryToDataBase("SELECT name FROM reference_data WHERE reference_data_id=" . $id)["name"];
    }

    public function setFieldsDataByTemplate($fieldValues, $templateFields)
    {
        $this->setTablesOntype();
        $this->fields = \ClassesOperations::autoLoadClass('\Controller\Fields', '/controllers/Fields.php');
        $this->fields->setFilteredTypes();

        foreach ($templateFields as $keyField => $templateFieldValue) {
            if (isset($fieldValues[$templateFieldValue["textId"]])) {
                $templateFieldValue["value"] = $fieldValues[$templateFieldValue["textId"]];
                if ($templateFieldValue['id'] > 0) {
                    $this->updateFieldByType($templateFieldValue);
                    $this->fields->setFilterValueForPage($templateFieldValue, $this->structId, $this->structTemplateId);
                } else {
                    $this->updateGeneralField($templateFieldValue);
                }
            }
        }
    }

    public function setTablesOntype()
    {
        if (!count($this->tablesOnType)) $this->tablesOnType = $this->getTablesOnType();
    }

    public function createDopProperties($id)
    {
        \DataBase::justQueryToDataBase("INSERT dop_properties_page SET id =" . $id);
    }

    public function getFieldsByPageIdForClient($props)
    {
        $propsName = "";
        if (count($props) > 0) {
            $propsName .= "AND (";
            foreach ($props as $key => $value) {
                if ($key != 0) {
                    $propsName .= " OR";
                }
                $propsName .= " fin.text_id = '" . $value . "'";
            }
            $propsName .= ")";
        }

        $fieldsToDbQuery = \DataBase::justQueryToDataBase("
				SELECT gif.field_id as id, fif.type_id as type_id, fin.name as name, fin.text_id as text_id
				FROM template_id_group_id tig
				INNER JOIN group_id_field_id gif
				ON tig.group_id = gif.group_id
				INNER JOIN field_id_field_type fif
				ON gif.field_id = fif.id
				INNER JOIN field_id_name fin
				ON fif.id = fin.id " . $propsName . "
				WHERE tig.template_id = " . $this->structTemplateId
        );

        $props = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($fieldsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $dataId = $responseFromDb["id"];
            $dataTypeId = $responseFromDb["type_id"];
            $dataValue = $this->getFieldValue($dataId, $dataTypeId, $this->structId);
            if ($dataValue != "" || ($dataTypeId == 10 && $dataValue != "00:00:00")) {
                $dataName = $responseFromDb["text_id"];
                $data = [];
                $data["id"] = $responseFromDb["id"];
                $data["text"] = $responseFromDb["name"];
                $data["value"] = $dataValue;
                $data["typeId"] = $responseFromDb["type_id"];
                if ($data["typeId"] == 4) {
                    $data["referenceId"] = $this->getReferenceIdByFieldId($data["id"]);
                }
                $props[$dataName] = $data;
            }
        }
        return $props;
    }

    public function getUriPage($id)
    {
        $uriReqDb = \DataBase::queryToDataBase("SELECT uri FROM page_id_uri WHERE page_id = " . $id)['uri'];
        return $uriReqDb;
    }

    public function updateFullUriChildrenPages($parent_id, $path)
    {
        $parentPagesReqToDb = \DataBase::justQueryToDataBase("SELECT pgprnt.id, pguri.uri  FROM page_parent_id  pgprnt INNER JOIN page_id_uri pguri ON pgprnt.id = pguri.page_id WHERE parent_id = " . $parent_id);
        while ($responseFromDb = \DataBase::responseFromDataBase($parentPagesReqToDb)) {
            $subPath = $path . $responseFromDb['uri'] . '/';
            $this->setFullUriPage($subPath, $responseFromDb['id']);
            $this->updateFullUriChildrenPages($responseFromDb['id'], $subPath);
        }
    }

    public function getTablesOnType()
    {
        $tablesOnType = [];
        $tabelsOnTypeFromDbQuery = \DataBase::justQueryToDataBase("SELECT table_name, id FROM type_fields");
        while ($responseFromDb = \DataBase::responseFromDataBase($tabelsOnTypeFromDbQuery)) {
            $tablesOnType[$responseFromDb["id"]] = $responseFromDb["table_name"];
        }
        return $tablesOnType;
    }

    public function getTypeTables()
    {
        $typesTable = [];
        $typesTableFromDbQuery = \DataBase::justQueryToDataBase("SELECT distinct table_name FROM type_fields");
        while ($responseFromDb = \DataBase::responseFromDataBase($typesTableFromDbQuery)) {
            array_push($typesTable, $responseFromDb['table_name']);
        }
        return $typesTable;
    }

    public function getReferenceIdByFieldId($field_id)
    {
        return \DataBase::queryToDataBase("SELECT reference_id FROM field_id_reference_id WHERE field_id = " . $field_id)["reference_id"];
    }

    private function updateGeneralField($fieldValue)
    {
        //тип данных
        if ($fieldValue['id'] == -1) {
            $this->setTypePage($fieldValue['value']);
        } else if ($fieldValue['id'] == -7) {
            $this->setMainPage($fieldValue['value']);
            //псевдостатический адрес
        } else if ($fieldValue['id'] == -2) {
            $this->setterUriAndChildrenPath($fieldValue['value']);
            //активная
        } else if ($fieldValue['id'] == -3) {
            $this->setActivePage($fieldValue['value']);
            //h1
        } else if ($fieldValue['id'] == -4) {
            $this->setH1Page($fieldValue['value']);
            //title
        } else if ($fieldValue['id'] == -5) {
            $this->setTitlePage($fieldValue['value']);
            //description
        } else if ($fieldValue['id'] == -6) {
            $this->setDescriptionPage($fieldValue['value']);
        } else if ($fieldValue['id'] == -8) {
            $this->setIndexedPage($fieldValue['value']);
        } else if ($fieldValue['id'] == -301) {
            $this->setAccessUser($fieldValue['value']);
        }
    }

    private function setMainPage($fieldValue)
    {
        $subDomainId = $this->getSubDomainIdForPage($this->structId);
        if ($this->isMainPageId($this->structId)) {
            if ($fieldValue['value'] == 1) {
                $this->setMainPageForSubDomain($this->structId, $subDomainId);
            } else {
                $this->setMainPageForSubDomain(0, $subDomainId);
            }
        } else if ($fieldValue['value'] == 1) {
            $this->setMainPageForSubDomain($this->structId, $subDomainId);
        }
    }

    private function setMainPageForSubDomain($id, $subDomainId)
    {
        $this->clearMainPageForSubDomain($subDomainId);
        \DataBase::justQueryToDataBase("INSERT main_page SET id=$id, sub_domain=$subDomainId");
    }

    private function clearMainPageForSubDomain($subDomainId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM main_page WHERE sub_domain= $subDomainId");
    }

    private function getSubDomainIdForPage($id)
    {
        return \DataBase::queryToDataBase("SELECT sub_domain FROM page_parent_id WHERE id=$id")['sub_domain'];
    }

    private function isMainPageId($id)
    {
        $pageController = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
        return !!$pageController->isMainPageId($id);
    }

    private function setAccessUser($fieldValue)
    {
        \DataBase::justQueryToDataBase("UPDATE users SET is_admin = " . $fieldValue . " WHERE user_id = " . $this->structId);
    }

    private function setDescriptionPage($fieldValue)
    {
        \DataBase::justQueryToDataBase("UPDATE dop_properties_page SET description = '" . $fieldValue . "' WHERE id = " . $this->structId);
    }


    private function setTitlePage($fieldValue)
    {
        \DataBase::justQueryToDataBase("UPDATE dop_properties_page SET title = '" . $fieldValue . "' WHERE id = " . $this->structId);
    }


    private function setH1Page($fieldValue)
    {
        \DataBase::justQueryToDataBase("UPDATE dop_properties_page SET h1 = '" . $fieldValue . "' WHERE id = " . $this->structId);
    }

    private function setIndexedPage($fieldValue)
    {
        \DataBase::justQueryToDataBase("UPDATE page_id_active SET no_indexed = " . $fieldValue . " WHERE id = " . $this->structId);
    }

    private function setActivePage($fieldValue)
    {
        \DataBase::justQueryToDataBase("UPDATE page_id_active SET active = " . $fieldValue . " WHERE id = " . $this->structId);
    }

    private function setUriPage($uriValue)
    {
        if (!$this->thereIsPathInSubDomain($uriValue)) {
            \DataBase::justQueryToDataBase("UPDATE page_id_uri SET uri = '" . $uriValue . "' WHERE page_id = " . $this->structId);
        }
    }

    private function setFullUriPage($uriValue, $id)
    {
        \DataBase::justQueryToDataBase("UPDATE page_id_uri SET full_path = '" . $uriValue . "' WHERE page_id = " . $id);
    }

    private function thereIsPathInSubDomain($uriValue)
    {
        $subDomain = \ClassesOperations::autoLoadClass('\SitePaths', '/libs/root-src/SitePaths.php');
        $subDomainId = $subDomain->getPageSubDomainId($this->structId);
        return !!\DataBase::queryToDataBase("SELECT piuri.page_id FROM page_id_uri piuri INNER JOIN page_parent_id ppid ON (piuri.page_id = ppid.id AND ppid.sub_domain=$subDomainId) WHERE piuri.uri = '$uriValue'");
    }

    private function changeOtherMainPage()
    {
        $parentPagesReqToDb = \DataBase::justQueryToDataBase("SELECT page_id as id FROM page_id_uri WHERE full_path = '/'");
        while ($responseFromDb = \DataBase::responseFromDataBase($parentPagesReqToDb)) {
            $path = $this->getUriPage($responseFromDb['id']);
            $full_path = $this->updateFullPathUri($path, $responseFromDb['id']);
            $this->updateFullUriChildrenPages($responseFromDb['id'], $full_path);
        }
    }

    private function updateFullPathUri($uriValue, $id, $main = false)
    {
        $parent_id = $this->getParentId();
        if ((int)$parent_id !== 0) {
            $parent_full_path = $this->getFullPathUri($parent_id);
            $full_path = $parent_full_path . $uriValue . '/';
            $this->setFullUriPage($full_path, $id);
        } else {

            if ($main)
                $full_path = '/';
            else
                $full_path = '/' . $uriValue . '/';
            $this->setFullUriPage($full_path, $id);
        }
        return $full_path;
    }

    private function getFullPathUri($id)
    {
        return \DataBase::queryToDataBase("SELECT full_path FROM page_id_uri WHERE page_id = " . $id)["full_path"];
    }

    private function getParentId()
    {
        return \DataBase::queryToDataBase("SELECT parent_id FROM page_parent_id WHERE id = " . $this->structId)["parent_id"];
    }

    private function setTypePage($fieldValue)
    {
        \DataBase::justQueryToDataBase("UPDATE page_id_to_template_id SET template_id = '" . $fieldValue . "' WHERE page_id = " . $this->structId);
    }

    private function updateFieldByType($fieldValue)
    {
        if (gettype($fieldValue['value']) == "string") {
            $fieldValue['value'] = trim($fieldValue['value']);
        }

        if ($fieldValue['typeId'] == 9) {
            if ($this->verifyFieldValueDate($fieldValue)) {
                $this->updateFieldOnType($fieldValue);
            }
        } else if ($fieldValue['typeId'] == 14 || $fieldValue['typeId'] == 5) {
            $this->setterFieldMultiValues($fieldValue, $this->tablesOnType[$fieldValue['typeId']]);
        } else if ($fieldValue['typeId'] == 10) {
            if ($this->verifyFieldValueTime($fieldValue)) {
                $fieldValue['value'] = $this->getTimeWithSeconds($fieldValue);
                $this->updateFieldOnType($fieldValue);
            }
        } else if ($fieldValue['typeId'] == 15) {
            $users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
            $fieldValue['value'] = $users->createHashPassword($fieldValue['value']);
            $this->updateFieldOnType($fieldValue);
        } else if ($fieldValue['typeId'] == 12) {
            $this->setFieldValueSostav($fieldValue, $this->tablesOnType[$fieldValue['typeId']]);
        } else {
            $this->updateFieldOnType($fieldValue);
        }
    }

    private function updateFieldOnType($fieldValue)
    {
        if ($this->tablesOnType[$fieldValue['typeId']]) {
            $this->setFieldValue($fieldValue, $this->tablesOnType[$fieldValue['typeId']]);
        } else {
            printf("Invalid data type");
            exit();
        }
    }

    private function setFieldValueSostav($fieldValue, $tableName)
    {
        //сначала удаляем все значения для этого поля
        $this->deleteMultiValue($fieldValue, $tableName);

        //потом устанавливаем новые
        $this->setFieldSostavValues($fieldValue, $tableName);
    }

    private function setFieldSostavValues($fieldValue, $tableName)
    {
        foreach ($fieldValue['value'] as $value) {
            $this->setFieldSostavValue($value, $tableName, $fieldValue['id']);
        }
    }

    private function setFieldSostavValue($fieldValue, $tableName, $fieldId)
    {
        \DataBase::justQueryToDataBase("INSERT " . $tableName . " SET value = " . $fieldValue['value'] . " , reference_data_id = '" . $fieldValue['id'] . "', page_id = " . $this->structId . ", type=" . $this->structTypeId . ", field_id = " . $fieldId);
    }

    private function getTimeWithSeconds($fieldValue)
    {
        $timeExplode = explode(':', $fieldValue['value']);
        if ($timeExplode[2] == '') {
            $timeToReturn = $timeExplode[0] . ':' . $timeExplode[1] . ':00';
        } else {
            $timeToReturn = $fieldValue['value'];
        }
        return $timeToReturn;
    }

    private function verifyFieldValueDate($fieldValue)
    {
        $date = new \DateTime($fieldValue['value']);
        if ($date->format('Y-m-d')) {
            return true;
        }
    }

    private function verifyFieldValueTime($fieldValue)
    {
        $timeExplode = explode(':', $fieldValue['value']);
        $timeExplode[0] = (int)$timeExplode[0];
        $timeExplode[1] = (int)$timeExplode[1];
        if (is_int($timeExplode[0]) && is_int($timeExplode[1])) return true;
    }

    private function setterFieldMultiValues($fieldValue, $tableName)
    {
        //сначала удаляем все значения для этого поля
        $this->deleteMultiValue($fieldValue, $tableName);

        //потом устанавливаем новые
        $this->setFieldMultiValues($fieldValue, $tableName);
    }

    private function setFieldMultiValues($fieldValue, $tableName)
    {
        foreach ($fieldValue['value'] as $value) {
            if ($value != '') {
                $fieldValueNw = [];
                $fieldValueNw['value'] = $value;
                $fieldValueNw['id'] = $fieldValue['id'];
                $this->setFieldMultiValue($fieldValueNw, $tableName);
            }
        }
    }

    private function setFieldMultiValue($fieldValue, $tableName)
    {
        \DataBase::justQueryToDataBase("INSERT " . $tableName . " SET value = '" . $fieldValue['value'] . "', page_id = " . $this->structId . ", type=" . $this->structTypeId . ", field_id = " . $fieldValue['id']);
    }

    private function deleteMultiValue($fieldValue, $tableName)
    {
        \DataBase::justQueryToDataBase("DELETE FROM " . $tableName . " WHERE page_id =" . $this->structId . " and field_id=" . $fieldValue['id'] . " and type=" . $this->structTypeId);
    }

    private function setFieldValue($fieldValue, $tableName)
    {
        if ($this->getValueField($fieldValue, $tableName)) {
            $this->updateField($fieldValue, $tableName);
        } else {
            $this->createField($fieldValue, $tableName);
        }
    }

    private function getValueField($fieldData, $tableName)
    {
        $pageTemplateFromDbQuery = \DataBase::queryToDataBase("SELECT value FROM " . $tableName . " WHERE page_id = " . $this->structId . " and type=" . $this->structTypeId . " and field_id = " . $fieldData['id']);
        return $pageTemplateFromDbQuery;
    }

    private function updateField($fieldValue, $tableName)
    {	
		\DataBase::justQueryToDataBase("UPDATE " . $tableName . " SET value = " . $this->getSqlResultValue($fieldValue['value']) . " WHERE page_id = " . $this->structId . " and type=" . $this->structTypeId . " and field_id = " . $fieldValue['id']);
	}

    private function createField($fieldValue, $tableName)
    {
        \DataBase::justQueryToDataBase("INSERT " . $tableName . " SET value = " . $this->getSqlResultValue($fieldValue['value']) . ", page_id = " . $this->structId . ", type=" . $this->structTypeId . ", field_id = " . $fieldValue['id']);
    }
	
	private function getSqlResultValue($fieldValue)
    {	
		$value = is_numeric($fieldValue) ? (int)$fieldValue : addslashes ($fieldValue);
		$resultValue = (empty($value) && $value !== false && $value !== 0) ? 'NULL' : $value;
		$sqlValue = $resultValue === 'NULL' ? $resultValue : ("'".$resultValue."'");
		return $sqlValue;
    }

    private function getFieldsForGroup($group_id)
    {
        $fieldsToDbQuery = \DataBase::justQueryToDataBase("
				SELECT gif.field_id, gif.group_id, fin.name, fin.text_id, fit.type_id, dpf.hint, dpf.necessarily FROM group_id_field_id gif
				INNER JOIN field_id_name fin
				ON gif.field_id = fin.id
				INNER JOIN field_id_field_type fit
				ON fit.id = gif.field_id
				INNER JOIN dop_properties_fields dpf
				ON dpf.id = gif.field_id
				WHERE gif.group_id = " . $group_id . "
				ORDER BY sort_num
				"
        );

        while ($responseFromDb = \DataBase::responseFromDataBase($fieldsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            if (!is_array($this->structData["fields"])) $this->structData["fields"] = [];
            $fieldNum = count($this->structData["fields"]);
            $this->structData["fields"][$fieldNum] = [];
            $this->structData["fields"][$fieldNum]["id"] = $responseFromDb["field_id"];
            $this->structData["fields"][$fieldNum]["parentId"] = $responseFromDb["group_id"];
            $this->structData["fields"][$fieldNum]["name"] = $responseFromDb["name"];
            $this->structData["fields"][$fieldNum]["textId"] = $responseFromDb["text_id"];
            $this->structData["fields"][$fieldNum]["typeId"] = $responseFromDb["type_id"];
            if ($responseFromDb["hint"]) $this->structData["fields"][$fieldNum]["hint"] = $responseFromDb["hint"];
            $this->structData["fields"][$fieldNum]["necessarily"] = $responseFromDb["necessarily"];
            $this->getReferenceNumForField($responseFromDb["type_id"], $fieldNum, $responseFromDb["field_id"]);
            $valueString = $this->getFieldValue($responseFromDb["field_id"], $responseFromDb["type_id"], $this->structId);
            if (is_string($valueString)) {
                $valueString = htmlspecialchars($valueString);
            }
            $this->structData["fields"][$fieldNum]["value"] = $valueString;
        }
    }

    private function selectFieldSostavValues($page_id, $field_id, $table_name)
    {
        $fieldsToDbQuery = \DataBase::justQueryToDataBase("SELECT value, reference_data_id FROM " . $table_name . " WHERE page_id = " . $page_id . " AND type=" . $this->structTypeId . " AND field_id=" . $field_id);
        $fieldValue = [];
        require CURRENT_WORKING_DIR . '/controllers/References.php';
        $references_model = new \Controller\References();
        while ($responseFromDb = \DataBase::responseFromDataBase($fieldsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $sostav_list = [];
            $sostav_list['id'] = $responseFromDb['reference_data_id'];
            $sostav_list['value'] = $responseFromDb['value'];
            $sostav_list['name'] = $references_model::getReferenceDataNameById($responseFromDb['reference_data_id']);
            array_push($fieldValue, $sostav_list);
        }
        return $fieldValue;
    }

    private function selectFieldValuesLinkPages($page_id, $field_id, $table_name)
    {

        $fieldsToDbQuery = \DataBase::justQueryToDataBase("SELECT tbl.value, piuri.full_path as 'url' FROM " . $table_name . " as tbl INNER JOIN page_id_uri piuri ON tbl.value = piuri.page_id  WHERE tbl.page_id = " . $page_id . " AND tbl.type=" . $this->structTypeId . " AND tbl.field_id=" . $field_id);
        $fieldValue = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($fieldsToDbQuery)) // перебор строк таблицы с начала до конца
        {

            $page_link = [];
            $page_link['id'] = $responseFromDb['value'];
            $page_link['url'] = $responseFromDb['url'];
            $page_link['name'] = Page::getPageName($responseFromDb['value']);

            array_push($fieldValue, $page_link);
        }
        return $fieldValue;
    }

    private function selectFieldValues($page_id, $field_id, $table_name)
    {
        $fieldsToDbQuery = \DataBase::justQueryToDataBase("SELECT value FROM " . $table_name . " WHERE page_id = " . $page_id . " AND type=" . $this->structTypeId . " AND field_id=" . $field_id);
        $fieldValue = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($fieldsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            array_push($fieldValue, $responseFromDb['value']);
        }
        return $fieldValue;
    }

    private function selectFieldValue($page_id, $field_id, $table_name)
    {
        $sql = "SELECT value FROM " . $table_name . " WHERE page_id = " . $page_id . " AND type=" . $this->structTypeId . " AND field_id=" . $field_id;
        $pageNameFromDbQuery = \DataBase::queryToDataBase($sql);
        return $pageNameFromDbQuery['value'];
    }

    private function getStructGroups($id)
    {
        $groupsToDbQuery = \DataBase::justQueryToDataBase("SELECT tig.group_id, gin.name, gin.text_id, gin.id FROM template_id_group_id tig INNER JOIN group_id_name gin ON tig.group_id = gin.id WHERE tig.template_id = " . $id);
        $this->structData['groups'] = [];
        $this->setTablesOntype();
        $groupNum = 1;
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery)) {
            $this->structData['groups'][$groupNum] = [];
            $this->structData['groups'][$groupNum]["name"] = $responseFromDb['name'];
            $this->structData['groups'][$groupNum]["textId"] = $responseFromDb['text_id'];
            $this->structData['groups'][$groupNum]["id"] = $responseFromDb['id'];
            $this->getFieldsForGroup($responseFromDb['id']);
            $groupNum++;
        }
    }

    private function getReferenceNumForField($typeId, $fieldNum, $fieldId)
    {
        if ($typeId == 4 || $typeId == 5 || $typeId == 12) {
            $this->structData["fields"][$fieldNum]["referenceId"] = \DataBase::queryToDataBase("SELECT reference_id FROM field_id_reference_id WHERE field_id=" . $fieldId)["reference_id"];
        }
    }
}
