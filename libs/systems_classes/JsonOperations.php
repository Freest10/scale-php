<?php
use  \Controller\TemplatesType as TemplatesType;
	class JsonOperations {

		public static function getJsonFromPhp($data, $jsonOption = JSON_NUMERIC_CHECK){
			if(PHP_VERSION_ID < 50400 && $jsonOption == JSON_UNESCAPED_UNICODE){
				array_walk_recursive($data, function (&$item, $key) { if (is_string($item)) $item = mb_encode_numericentity($item, array (0x80, 0xffff, 0, 0xffff), 'UTF-8'); });
				return mb_decode_numericentity(json_encode($data), array (0x80, 0xffff, 0, 0xffff), 'UTF-8');
			}else{
				return json_encode($data, $jsonOption);
			}
		}
		
		static function printJsonFromPhp($data, $jsonOption = JSON_NUMERIC_CHECK){
			echo(json_encode($data, $jsonOption));
		}

		public static function createLimitJson($params){
			$sqlString = $params['selectResponse'];
			if(($params['begin']> -1) && $params['limit']){
			    $limit=$params['begin']+$params['limit'];
				$sqlString .= ' LIMIT '.$params['begin'].','.$limit;
			}

			$referencesToDbQuery = \DataBase::justQueryToDataBase($sqlString);
			$limitJson = [];
			$langs = \Langs::getInstance();
			while($responseFromDb = \DataBase::responseFromDataBase($referencesToDbQuery)) // перебор строк таблицы с начала до конца
			{
				if(!is_array($limitJson["items"])) $limitJson["items"] = [];
				$itemNum = count($limitJson["items"]);
				$limitJson['items'][$itemNum] = [];
				if($params['type'] == 'reference'){
					$limitJson['items'][$itemNum]['name'] = TemplatesType::getTemplateName($responseFromDb['id']);
				}else if($params['type'] == 'order'){
					$limitJson['items'][$itemNum]['name'] = $langs->getMessage("backend.emarket.order").' №'.$responseFromDb['id'];
				}
				
				foreach ($params['templatesData'] as $key => $value){
					$limitJson['items'][$itemNum][$key] =  $responseFromDb[$key];
				}
			}
			
			if($params['begin']> -1){
				$limitJson['begin'] = $params['begin'];
			}
			
			if($params['limit']){
				$limitJson['limit'] = $params['limit'];
			}

			$limitJson['total'] = \DataBase::countRowsTable($params['countResponse']);
			\JsonOperations::printJsonFromPhp($limitJson);
		}
	}
?>