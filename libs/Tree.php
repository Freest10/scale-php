<?php 

	class Tree{
		
		private $treeArray = array();
		
		public function jsTree($sql, $firstNodeName = false, $hideFirstNode = false){
			
			$sqlVar = \DataBase::justQueryToDataBase($sql);
				//iterate on results row and create new index array of data
				while( $row = \DataBase::responseFromDataBase($sqlVar) ) {
					$row['a_attr'] = [];
					$row['a_attr']['href'] = $row["href"] ? $row["href"] : $row['id'];
					$data[] = $row;
				}
				$itemsByReference = array();
			// Build array of item references:
			foreach($data as $key => &$item) {
			   $itemsByReference[$item['id']] = &$item;
			  // $itemsByReference[$item['a_attr']] = array();
			   // Children array:
			   $itemsByReference[$item['id']]['children'] = array();
			   // Empty data class (so that json_encode adds "data: {}" ) 
			   $itemsByReference[$item['id']]['data'] = new StdClass();
			}
			// Set items as children of the relevant parent item.
			foreach($data as $key => &$item)
			   if($item['parent_id'] && isset($itemsByReference[$item['parent_id']]))
				  $itemsByReference [$item['parent_id']]['children'][] = &$item;
			// Remove items that were added to parents elsewhere:
			foreach($data as $key => &$item) {
			   if($item['parent_id'] && isset($itemsByReference[$item['parent_id']]))
				  unset($data[$key]);
			}

			$this->treeArray = [];
			$this->treeArray["children"] = array_values($data);
			$this->treeArray["state"] = [];
			$this->treeArray["state"]["opened"] = true;
			if($firstNodeName) $this->treeArray["text"] = $firstNodeName;
			if($hideFirstNode){
				$this->treeArray["li_attr"] = [];
				$this->treeArray["li_attr"]["class"] = "hideLi";
			}

		}
		
		
		public function get_page_parent_id($requestString) {
			$pageParentToDbQuery = \DataBase::justQueryToDataBase($requestString);
			$this->doTree($pageParentToDbQuery);
		}
		
		public function doTree($pageParentToDbQuery) {
					
			while($responseFromDb = \DataBase::responseFromDataBase($pageParentToDbQuery)) // ������� ����� ������� � ������ �� �����
			{	

				$this->treeArray["state"] = array();
				$this->treeArray["li_attr"] = array();
				$this->treeArray["state"]["opened"] = true;

				//������ ������� ������
				if($responseFromDb["parent_id"] == 0){
					$numPage = count($this->treeArray['children']); 
					if($numPage == 0) $this->treeArray['children'] = array();
					$this->treeArray['children'][$numPage]['id'] = $responseFromDb["id"];
					$this->treeArray['children'][$numPage]['parent_id'] = $responseFromDb["parent_id"];
					$this->treeArray['children'][$numPage]['a_attr'] = array();
					$this->treeArray['children'][$numPage]['a_attr']['href'] = $responseFromDb["href"] ? $responseFromDb["href"] : $responseFromDb["id"];
					$this->treeArray['children'][$numPage]['text'] = $responseFromDb["name"];
				}else{
					$this->rekTreeId($responseFromDb, []);	
				}
			}
			
		}
				
		private function rekTreeId($pageData, $arrayNumChildrens){
			
			$firstLevelList = '$this->treeArray[children]';
			$levelTree =  count($arrayNumChildrens);
			for($i=0;$i<$levelTree;$i++){
				$firstLevelList .= '['.$arrayNumChildrens[$i].']["children"]';
			}
			//$pageData["name"]
			eval('foreach ('.$firstLevelList.' as $keyLevelValue => $levelValue){ 

				if($levelValue["id"] == $pageData["parent_id"]){
					
					if(!is_array('.$firstLevelList.'["$keyLevelValue"]["children"])){'.$firstLevelList.'["$keyLevelValue"]["children"]=array();};
					$numsElementsInChildrenMassive = count($levelValue["children"]);
					'.$firstLevelList.'["$keyLevelValue"]["children"][$numsElementsInChildrenMassive]["id"] = $pageData["id"];
					'.$firstLevelList.'["$keyLevelValue"]["children"][$numsElementsInChildrenMassive]["text"] = $pageData["name"];
					'.$firstLevelList.'["$keyLevelValue"]["children"][$numsElementsInChildrenMassive]["a_attr"] = array();
					'.$firstLevelList.'["$keyLevelValue"]["children"][$numsElementsInChildrenMassive]["a_attr"]["href"] = $pageData["href"] ? $pageData["href"] : $pageData["id"];
				}else{

					if(is_array('.$firstLevelList.'["$keyLevelValue"]["children"])){
						array_push($arrayNumChildrens, $keyLevelValue);
						$this->rekTreeId($pageData,$arrayNumChildrens);
					}
				}

			}');
		}
		
		public function addPageName($queryNames){
			$pageParentToDbQuery = \DataBase::justQueryToDataBase($queryNames);
			$this->doNamesToTree($pageParentToDbQuery);
		}
		
		public function doNamesToTree($pageNamesDbQuery){
			while($responseFromDb = \DataBase::responseFromDataBase($pageNamesDbQuery)) // ������� ����� ������� � ������ �� �����
			{	
				//print_r($responseFromDb);
				$this->rekTreeNames($responseFromDb, []);
			}
		}
		
		private function rekTreeNames($pageData, $arrayNumChildrens){
			//print_r($pageData);
			$firstLevelList = '$this->treeArray[children]';
			$levelTree = count($arrayNumChildrens);
			
			for($i=0;$i<$levelTree;$i++){
				$firstLevelList .= '['.$arrayNumChildrens[$i].']["children"]';
			}
			//$pageData["name"]
			eval('foreach ('.$firstLevelList.' as $keyLevelValue => $levelValue){ 
				if($levelValue["id"] == $pageData["id"]){
					'.$firstLevelList.'["$keyLevelValue"]["text"] = $pageData["name"];
				}else{
					if(is_array('.$firstLevelList.'["$keyLevelValue"]["children"])){
						array_push($arrayNumChildrens, $keyLevelValue);
						$this->rekTreeNames($pageData,$arrayNumChildrens);
					}
				}
			}');
		}
		
		public function addAttrsOfMainNodeOfTree($attrs){
			foreach ($attrs as $keyAttr => $valueAttr){ 
				$this->treeArray[$keyAttr]=$valueAttr;
			}
		}
		
		public function addHideFirstNode(){
			$this->treeArray["li_attr"]["class"] = "hideLi";
		}
		
		public function jsonDecode(){
			return json_encode($this->treeArray);
		}
		
	}

?>