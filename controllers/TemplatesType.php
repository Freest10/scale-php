<?php

namespace Controller;
class TemplatesType
{

    public static function getTemplateName($id)
    {
        return \DataBase::queryToDataBase("SELECT name FROM template_id_name WHERE id = " . $id)['name'];
    }

    public static function isSetTemplate($id)
    {
        $templateName = \DataBase::queryToDataBase("SELECT id FROM template_id_name WHERE id = " . $id);
        return $templateName['id'] ? true : false;
    }

    public $templateData;
    public $id;
    public $langs;
    private $reqGroupsId;
    private $reqExistGroupId;
    private $fieldClass;
    private $typesMacros;

    function __construct()
    {
        $this->langs = \Langs::getInstance();
    }

    public function getTemplateById($id)
    {
        $this->setObjectTemplateName($this->getTemplateName($id));
        $this->getTemplateGroups($id);
        printf($this->getJsonTemplate());
    }

    public function addTemplate($parent_id)
    {
        if ($parent_id) {
            $this->setTemplateName();
            $lstId = $this->getIdLastTemplate();
            $this->setTemplateParentId($parent_id, $lstId);
            $this->setGroupsFromParentTemplate($lstId, $parent_id);
        } else {
            $this->setTemplateName();
            $lstId = $this->getIdLastTemplate();
            $this->setTemplateParentId(NULL, $lstId);
        }
        \Response::goodResponse();
    }

    public function deleteTemplate($id)
    {
        $systemTemplates = [1, 2, 3, 4, 5, 6];
        if (in_array($id, $systemTemplates)) {
            \Response::errorResponse($this->langs->getMessage("backend.errors.system_template"));
        } else {
            $this->id = $id;
            $this->deleteTemplateById();
            $this->fieldClass = new fields;
            $this->deleteGroupsForDeleteTemplate();
            \Response::goodResponse();
        }
    }

    private function deleteGroupsForDeleteTemplate()
    {
        $groupsForTemplateDB = \DataBase::justQueryToDataBase("SELECT group_id FROM template_id_group_id WHERE template_id = " . $this->id);
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsForTemplateDB)) // перебор строк таблицы с начала до конца
        {
            $this->deleteGroupFromTemplate($responseFromDb['group_id']);
            if (!$this->isHaveGroupInTemplate($responseFromDb['group_id'])) {
                $this->deleteFieldForDeletedGroups($responseFromDb['group_id']);
                $this->deleteDeleteGroup($responseFromDb['group_id']);
            }
        }
    }

    private function isHaveGroupInTemplate($group_id)
    {
        $groupsForTemplateDB = \DataBase::queryToDataBase("SELECT template_id FROM template_id_group_id WHERE group_id = " . $group_id);
        return $groupsForTemplateDB['template_id'];
    }

    public static function getTemplatesList()
    {
        $templateListToDbQuery = \DataBase::justQueryToDataBase("SELECT * FROM template_id_name");
        $templatesList = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($templateListToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $templateList = [];
            $templateList['id'] = $responseFromDb['id'];
            $templateList['name'] = $responseFromDb['name'];
            array_push($templatesList, $templateList);
        }
        return $templatesList;
    }

    private function deleteTemplateById()
    {
        $this->deleteTemplateName();
        $this->deleteTemplateParentId();
    }

    private function deleteTemplateName()
    {
        \DataBase::justQueryToDataBase("DELETE FROM template_id_name WHERE id =" . $this->id);
    }

    private function deleteTemplateParentId()
    {
        \DataBase::justQueryToDataBase("DELETE FROM template_parent_id WHERE id =" . $this->id);
    }

    private function getIdLastTemplate()
    {
        $lastInsrtTemplate = \DataBase::queryToDataBase("SELECT MAX(id) FROM template_id_name");
        return $lastInsrtTemplate['MAX(id)'];
    }

    private function setGroupsFromParentTemplate($id, $parent_id)
    {
        $groupsToDbQuery = \DataBase::justQueryToDataBase("SELECT group_id FROM template_id_group_id WHERE template_id = " . $parent_id);
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            \DataBase::justQueryToDataBase("INSERT INTO template_id_group_id SET template_id='" . $id . "', group_id = '" . $responseFromDb['group_id'] . "'");
        }
    }

    public function setTemplateName($id = NULL, $name = NULL)
    {
        if ((!$name && $name != 0) || $name == '') $name = $this->langs->getMessage("backend.new_template");

        if ($id) {
            $sqlCommand = "INSERT INTO template_id_name SET name='" . $name . "', id=" . $id . "";
        } else {
            $sqlCommand = "INSERT INTO template_id_name SET name='" . $name . "'";
        }
        \DataBase::justQueryToDataBase($sqlCommand);
    }

    private function setTemplateParentId($parent_id, $id)
    {
        $parentIdValue = $parent_id === NULL ? "NULL" : $parent_id;
        \DataBase::justQueryToDataBase("INSERT INTO template_parent_id SET id = " . $id . ", parent_id = " . $parentIdValue);
    }

    private function setObjectTemplateName($templateName)
    {
        $this->template['name'] = $templateName;
    }

    public function getTemplateGroups($id)
    {
        $groupsToDbQuery = \DataBase::justQueryToDataBase("SELECT tig.group_id, gin.name, gin.text_id, gin.id FROM template_id_group_id tig INNER JOIN group_id_name gin ON tig.group_id = gin.id WHERE tig.template_id = " . $id);
        $this->template['groups'] = [];
        $groupNum = 0;
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $this->template['groups'][$groupNum] = [];
            $this->template['groups'][$groupNum]["name"] = $responseFromDb['name'];
            $this->template['groups'][$groupNum]["textId"] = $responseFromDb['text_id'];
            $this->template['groups'][$groupNum]["id"] = $responseFromDb['id'];
            $this->getFieldsForGroup($responseFromDb['id'], $responseFromDb['text_id']);
            $groupNum++;
        }
    }

    public function getTemplateFields($id)
    {
        $groupsToDbQuery = \DataBase::justQueryToDataBase("SELECT gin.text_id, gin.id FROM template_id_group_id tig INNER JOIN group_id_name gin ON tig.group_id = gin.id WHERE tig.template_id = " . $id);
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery)) {
            $this->getFieldsForGroup($responseFromDb['id'], $responseFromDb['text_id']);
        }
    }

    public function getGroupIdByTextId($groupTextId)
    {
        return \DataBase::queryToDataBase("SELECT id FROM group_id_name WHERE text_id = '" . $groupTextId . "'")['id'];
    }

    private function getFieldsForGroup($group_id, $groupTextId)
    {
        $fieldsToDbQuery = \DataBase::justQueryToDataBase("
				SELECT gif.field_id, gif.group_id, fin.name, fin.text_id, fit.type_id, dpf.hint, dpf.necessarily, dpf.indexed, dpf.filtered  FROM group_id_field_id gif
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

            if (!is_array($this->template["fields"])) $this->template["fields"] = [];
            $fieldNum = count($this->template["fields"]);
            $this->template["fields"][$fieldNum] = [];
            $this->template["fields"][$fieldNum]["id"] = $responseFromDb["field_id"];
            $this->template["fields"][$fieldNum]["parentId"] = $responseFromDb["group_id"];
            $this->template["fields"][$fieldNum]["name"] = $responseFromDb["name"];
            $this->template["fields"][$fieldNum]["textId"] = $responseFromDb["text_id"];
            $this->template["fields"][$fieldNum]["typeId"] = $responseFromDb["type_id"];
            $this->template["fields"][$fieldNum]["parentTextId"] = $groupTextId;
            if ($responseFromDb["hint"]) $this->template["fields"][$fieldNum]["hint"] = $responseFromDb["hint"];
            $this->template["fields"][$fieldNum]["necessarily"] = $responseFromDb["necessarily"];
            $this->template["fields"][$fieldNum]["noIndex"] = $responseFromDb["indexed"];
            $this->template["fields"][$fieldNum]["filtered"] = $responseFromDb["filtered"];
            //смотрим id справочника для типа "выпадающий список" и "выпадающий список со множественным выбором"
            if ($responseFromDb["type_id"] == 4 || $responseFromDb["type_id"] == 5 || $responseFromDb["type_id"] == 12) $this->getDirectoryIdForField($fieldNum, $responseFromDb["field_id"]);
        }
    }

    private function getDirectoryIdForField($fieldNum, $field_id)
    {
        $referenceId = \DataBase::queryToDataBase("SELECT reference_id FROM field_id_reference_id WHERE field_id = " . $field_id);
        $this->template["fields"][$fieldNum]["referenceId"] = $referenceId["reference_id"];
    }

    public function getJsonTemplate()
    {
        return json_encode($this->template, JSON_NUMERIC_CHECK);
    }

    public function updateTemplateData()
    {
        $this->updateTemplateName($this->id, $this->templateData['name']);
        $this->updateTemplateGroups();
        $this->fieldClass = new Fields;
        $this->fieldClass->fields = $this->templateData['fields'];
        $this->fieldClass->groupsWithFields = $this->reqGroupsId;
        $this->fieldClass->setFieldsData($this->id);
        $this->deleteGroups();
        \Response::goodResponse();
    }

    public function updateTemplateName($id, $name)
    {
        \DataBase::queryToDataBase("UPDATE template_id_name SET name = '" . $name . "' WHERE id = " . $id);
    }

    private function updateTemplateGroups()
    {
        $this->reqGroupsId = [];
        foreach ($this->templateData['groups'] as $keyGroup => $groupValue) {
            //группы, у которых id - число, обновляем
            if ((int)$groupValue['id']) {
                $this->updateGroupValue($groupValue);
                //группы, у которых нет id добавляем в таблицу
            } else {
                $existGroupId = $this->getGroupIdByTextId($groupValue['textId']);
                if ($existGroupId) {
                    $groupValue['id'] = $existGroupId;
                    $this->updateGroup($groupValue);
                    $this->reqExistGroupId[$groupValue['id']] = true;
                    $this->setGroupToTemplate($existGroupId);
                } else {
                    $this->insertGroup($groupValue);
                }
            }
        }
    }

    private function updateGroupValue($groupValue)
    {
        $this->updateGroup($groupValue);
        $this->reqGroupsId[$groupValue['id']] = true;
    }

    private function deleteGroups()
    {
        $groupsForTemplateDB = \DataBase::justQueryToDataBase("SELECT group_id FROM template_id_group_id WHERE template_id = " . $this->id);
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsForTemplateDB)) // перебор строк таблицы с начала до конца
        {
            if (!$this->reqGroupsId[$responseFromDb['group_id']] && !$this->reqExistGroupId[$responseFromDb['group_id']]) {
                $this->deleteGroupFromTemplate($responseFromDb['group_id']);
                if (!$this->getCountedGroups($responseFromDb['group_id'])) {
                    $this->deleteFieldForDeletedGroups($responseFromDb['group_id']);
                    $this->deleteDeleteGroup($responseFromDb['group_id']);
                }
            }
        }
    }

    private function getCountedGroups($groupId)
    {
        return \DataBase::queryToDataBase("SELECT COUNT(template_id) as count FROM template_id_group_id WHERE group_id = " . $groupId)['count'];
    }

    private function deleteFieldForDeletedGroups($groupId)
    {
        $this->fieldClass->deleteFieldsByGroupId($groupId);
    }

    private function deleteDeleteGroup($groupId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM group_id_name WHERE id =" . $groupId);
    }

    private function deleteGroupFromTemplate($groupId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM template_id_group_id WHERE group_id =" . $groupId . " and template_id=" . $this->id);
    }

    private function updateGroup($groupValue)
    {
        \DataBase::justQueryToDataBase("UPDATE group_id_name SET name = '" . $groupValue['name'] . "', text_id='" . $groupValue['textId'] . "' WHERE id = " . $groupValue['id']);
    }

    private function insertGroup($groupValue)
    {
        \DataBase::justQueryToDataBase("INSERT INTO group_id_name SET name = '" . $groupValue['name'] . "', text_id='" . $groupValue['textId'] . "'");
        $lastInsrtGroupId = \DataBase::queryToDataBase("select last_insert_id()");
        $lastInsrtGroupId = $lastInsrtGroupId["last_insert_id()"];
        if ($lastInsrtGroupId != 0) {
            $this->reqGroupsId[$lastInsrtGroupId] = true;
            $this->setGroupToTemplate($lastInsrtGroupId);
        }
    }

    private function setGroupToTemplate($groupId)
    {
        \DataBase::justQueryToDataBase("INSERT template_id_group_id SET template_id=" . $this->id . ", group_id = " . $groupId);
    }

    public function getTypeForClient($options)
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
                $idsToSql .= " tin.id = " . $value;
            }
        }

        $typesQueryToDbQuery = \DataBase::justQueryToDataBase("
				SELECT tin.id as id, tin.name as name
				FROM template_id_name tin
				WHERE " . $idsToSql
        );

        while ($responseFromDb = \DataBase::responseFromDataBase($typesQueryToDbQuery)) {
            $data = [];
            $dataId = $responseFromDb["id"];
            $data["text"] = $responseFromDb["name"];
            if (count($props) > 0) {
                if (!$this->struct_data) {
                    $this->connectStructData();
                }
                $this->struct_data->structTemplateId = $responseFromDb["id"];
                $data["props"] = $this->struct_data->getFieldsByTypeIdForClient($props);
            }
            if (count($groups) > 0) {
                if (!$this->struct_data) {
                    $this->connectStructData();
                }
                $this->struct_data->structTemplateId = $responseFromDb["id"];
                $data["groups"] = $this->struct_data->getGroupsByTypeIdForClient($groups);
            }

            $this->typesMacros[$dataId] = $data;
        }
        return $this->typesMacros;
    }

    private function connectStructData()
    {
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
    }

    public function getClientTemplateById($id)
    {
        $this->setObjectTemplateName($this->getTemplateName($id));
        $this->getClientTemplateGroups($id);
        printf($this->getJsonTemplate());
    }

    public function getClientTemplateGroups($id)
    {
        $this->template['id'] = $id;
        $groupsToDbQuery = \DataBase::justQueryToDataBase("SELECT tig.group_id, gin.name, gin.text_id, gin.id FROM template_id_group_id tig INNER JOIN group_id_name gin ON tig.group_id = gin.id WHERE tig.template_id = " . $id);
        $this->template['groups'] = [];
        $groupNum = 0;
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $this->template['groups'][$responseFromDb['text_id']] = [];
            $this->template['groups'][$responseFromDb['text_id']]["text"] = $responseFromDb['name'];
            $this->template['groups'][$responseFromDb['text_id']]["id"] = $responseFromDb['id'];
            $this->template['groups'][$responseFromDb['text_id']]["props"] = [];
            array_push($this->template['groups'][$responseFromDb['text_id']]["props"], $this->getClientFieldsForGroup($responseFromDb['id'], $responseFromDb['text_id']));
            $groupNum++;
        }
    }

    private function getClientFieldsForGroup($group_id, $groupTextId)
    {
        $fieldsToDbQuery = \DataBase::justQueryToDataBase("
                SELECT gif.field_id, gif.group_id, fin.name, fin.text_id, fit.type_id, dpf.hint, dpf.necessarily, dpf.indexed, dpf.filtered  FROM group_id_field_id gif
                INNER JOIN field_id_name fin
                ON gif.field_id = fin.id
                INNER JOIN field_id_field_type fit
                ON fit.id = gif.field_id
                INNER JOIN dop_properties_fields dpf
                ON dpf.id = gif.field_id
                WHERE gif.group_id = " . $group_id
        );

        $props = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($fieldsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $props[$responseFromDb["text_id"]] = [];
            $props[$responseFromDb["text_id"]]["id"] = $responseFromDb["field_id"];
            $props[$responseFromDb["text_id"]]["text"] = $responseFromDb["name"];
            $props[$responseFromDb["text_id"]]["typeId"] = $responseFromDb["type_id"];
            $props[$responseFromDb["text_id"]]["necessarily"] = $responseFromDb["necessarily"];
            $props[$responseFromDb["text_id"]]["noIndex"] = $responseFromDb["indexed"];
            $props[$responseFromDb["text_id"]]["filtered"] = $responseFromDb["filtered"];
            if ($responseFromDb["type_id"] == 4 || $responseFromDb["type_id"] == 5 || $responseFromDb["type_id"] == 12) {
                $props[$responseFromDb["text_id"]]["referenceId"] = $this->getClientDirectoryIdForField($fieldNum, $responseFromDb["field_id"]);
            }
        }
        return $props;
    }

    private function getClientDirectoryIdForField($fieldNum, $field_id)
    {
        $referenceId = \DataBase::queryToDataBase("SELECT reference_id FROM field_id_reference_id WHERE field_id = " . $field_id);
        return $referenceId["reference_id"];
    }
}
