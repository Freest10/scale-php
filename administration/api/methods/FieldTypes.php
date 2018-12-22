<?php
namespace Api;
	class FieldTypes {
		
		private $field_types = [];
		
		public function get(){
			$this->getFieldTypes();
			print_r($this->getJsonFieldTypes());
		}
		
		private function getFieldTypes(){
			$fieldTypesToDbQuery = \DataBase::justQueryToDataBase("SELECT id, name FROM type_fields");
			while($responseFromDb = \DataBase::responseFromDataBase($fieldTypesToDbQuery)) // ������� ����� ������� � ������ �� �����
			{	
				$this->field_types[$responseFromDb['id']] = $responseFromDb['name'];
			}
		}

		private function getJsonFieldTypes(){
			return json_encode($this->field_types, JSON_NUMERIC_CHECK);
		}
	}