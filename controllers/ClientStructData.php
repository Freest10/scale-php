<?php
namespace Controller;
	class ClientStructData{
		
		public $structId;
		public $templateId;
		public $clientStructdata;
		private $struct_data_class;
		
		public function getGroupsWithIncludeProperties(){

			$groupsToDbQuery = \DataBase::justQueryToDataBase("SELECT tig.group_id, gin.name, gin.text_id, gin.id FROM template_id_group_id tig INNER JOIN group_id_name gin ON tig.group_id = gin.id WHERE tig.template_id = ".$this->templateId);

			$this->clientStructdata['groups'] = [];
			$this->struct_data_class = \ClassesOperations::autoLoadClass("\Controller\StructData", "/controllers/StructData.php");
			$this->struct_data_class->structTypeId = 1;
			$this->struct_data_class->structTemplateId = $this->templateId;

			while($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery))
			{
				$group_name = $responseFromDb['text_id'];
				$this->clientStructdata['groups'][$group_name] = [];
				$this->clientStructdata['groups'][$group_name]["title"] = $responseFromDb['name'];
				$this->clientStructdata['groups'][$group_name]["id"] = $responseFromDb['id'];
				$this->clientStructdata['groups'][$group_name]["fields"] = $this->getFieldsForGroup($responseFromDb['id']);
			}
			
			return $this->clientStructdata['groups'];
		}
		
		private function getFieldsForGroup($group_id){
			$fieldsToDbQuery = \DataBase::justQueryToDataBase("
				SELECT gif.field_id, gif.group_id, fin.name, fin.text_id, fit.type_id, dpf.hint, dpf.necessarily FROM group_id_field_id gif
				INNER JOIN field_id_name fin 
				ON gif.field_id = fin.id 
				INNER JOIN field_id_field_type fit
				ON fit.id = gif.field_id
				INNER JOIN dop_properties_fields dpf
				ON dpf.id = gif.field_id
				WHERE gif.group_id = ".$group_id
			);
			
			$fields = [];
			
			while($responseFromDb = \DataBase::responseFromDataBase($fieldsToDbQuery))
			{	
				$text_id = $responseFromDb["text_id"];
				$fields[$text_id] = [];
				$fields[$text_id]["id"] = $responseFromDb["field_id"];
				$fields[$text_id]["name"] = $responseFromDb["name"];
				$fields[$text_id]["typeId"] = $responseFromDb["type_id"];
				$fields[$text_id]["necessarily"] = $responseFromDb["necessarily"];
				$fields[$text_id]["value"] =  $this->struct_data_class->getFieldValue($responseFromDb["field_id"], $responseFromDb["type_id"], $this->structId);

				switch($responseFromDb["type_id"]){
                    case 4:
                    case 5:
                    $fields[$text_id]["referenceId"] = $this->struct_data_class->getReferenceIdByFieldId($responseFromDb["field_id"]);
                }
			}

			return $fields;
		}
	
	}