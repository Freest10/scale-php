<?php
namespace Macros;


class PugAdapter
{
    private $prefix = 'macro_';
    private $macros;

    function __construct($macros)
    {
        $this->macros = $macros;
    }

    function getMacros() {
        $macros = $this->macros;
        return [
            $this->prefix.'getPages' => function($options) use($macros){
                return $macros->getPages($options);
            },
			$this->prefix.'getTotalChildPages' => function($options) use($macros){
                return $macros->getTotalChildPages($options);
            },
			$this->prefix.'getCurrentDateMinusDays' => function($days) use($macros){
                return $macros->getCurrentDateMinusDays($days);
            },
			$this->prefix.'getMinMaxValue' => function($options) use($macros){
                return $macros->getMinMaxValue($options);
            },
			$this->prefix.'roundHalfToDown' => function($options) use($macros){
                return $macros->roundHalfToDown($options);
            },
			$this->prefix.'isThereProperty' => function($object, $propertyName) use($macros){
                return $macros->isThereProperty($object, $propertyName);
            },
			$this->prefix.'isEmptyArray' => function($array) use($macros){
                return $macros->isEmptyArray($array);
            },
            $this->prefix.'createPage' => function($parentId = 0, $pageName, $templateId = null) use($macros){
                return $macros->createPage($parentId, $pageName, $templateId);
            },
            $this->prefix.'getPagesById' => function($options) use($macros){
                return $macros->getPagesById($options);
            },
            $this->prefix.'changePageType' => function($pageId, $typeId) use($macros){
                return $macros->changePageType($pageId, $typeId);
            },
            $this->prefix.'changePageName' => function($pageId, $pageName) use($macros){
                return $macros->changePageName($pageId, $pageName);
            },
            $this->prefix.'changeGeneralFieldOfPage' => function($pageId, $propName, $value) use($macros){
                return $macros->changeGeneralFieldOfPage($pageId, $propName, $value);
            },
            $this->prefix.'updateFieldPage' => function($id, $fieldTextId, $value) use($macros){
                return $macros->updateFieldPage($id, $fieldTextId, $value);
            },
            $this->prefix.'thumbnail' => function($url, $options) use($macros){
                return $macros->thumbnail($url, $options);
            },
            $this->prefix.'getTypes' => function($options) use($macros){
                return $macros->getTypes($options);
            },
            $this->prefix.'getReferences' => function($options) use($macros){
                return $macros->getReferences($options);
            },
            $this->prefix.'getUsers' => function($options) use($macros){
                return $macros->getUsers($options);
            },
            $this->prefix.'getOrdersForUsers' => function($options) use($macros){
                return $macros->getOrdersForUsers($options);
            },
            $this->prefix.'getMessage' => function($id) use($macros){
                return $macros->getMessage($id);
            },
            $this->prefix.'sendMessage' => function($id, $value) use($macros){
                return $macros->sendMessage($id, $value);
            },
            $this->prefix.'addToBasket' => function($options) use($macros){
                return $macros->addToBasket($options);
            },
            $this->prefix.'updateBasket' => function($options) use($macros){
                return $macros->updateBasket($options);
            },
            $this->prefix.'getBasket' => function() use($macros){
                return $macros->getBasket();
            },
            $this->prefix.'setOrder' => function($values) use($macros){
                return $macros->setOrder($values);
            },
            $this->prefix.'getLastOrder' => function() use($macros){
                return $macros->getLastOrder();
            },
            $this->prefix.'search' => function($options) use($macros){
                return $macros->search($options);
            },
            $this->prefix.'getPath' => function() use($macros){
                return $macros->getPath();
            },
            $this->prefix.'getBreadCrumbs' => function() use($macros){
                return $macros->getBreadCrumbs();
            },
            $this->prefix.'removeUser' => function($id) use($macros){
                return $macros->removeUser($id);
            },
            $this->prefix.'createUser' => function($options) use($macros){
                return $macros->createUser($options);
            },
            $this->prefix.'editUser' => function($options) use($macros){
                return $macros->editUser($options);
            },
            $this->prefix.'logOutUser' => function() use($macros){
                return $macros->logOutUser();
            },
            $this->prefix.'authUser' => function($options) use($macros){
                return $macros->authUser($options);
            },
            $this->prefix.'restoreUser' => function($options) use($macros){
                return $macros->restoreUser($options);
            },
            $this->prefix.'passwordUserRecovery' => function($options) use($macros){
                return $macros->passwordUserRecovery($options);
            },
            $this->prefix.'isMobile' => function() use($macros){
                return $macros->isMobile();
            },
            $this->prefix.'setNotFoundHeader' => function() use($macros){
                return $macros->setNotFoundHeader();
            },
            $this->prefix.'setResponseHeaderCode' => function($code) use($macros){
                return $macros->setResponseHeaderCode($code);
            },
            $this->prefix.'getActiveSubDomain' => function() use($macros){
                return $macros->getActiveSubDomain();
            },
            $this->prefix.'getSubDomains' => function() use($macros){
                return $macros->getSubDomains();
            },
            'logger' => function($value){
                var_dump($value);
            },
			'showJson' => function($json){
                $myJSONString = json_encode($json);
				echo $myJSONString;
            },
			$this->prefix.'setJsonResponseHeaderContentType' => function() use($macros) {
                $macros->setJsonResponseHeaderContentType();
            },			
			$this->prefix.'setAccessControlAllowOrigin' => function() use($macros) {
                $macros->setAccessControlAllowOrigin();
            },			
			'arrayPush' => function($arrayForPushing, $value){
                array_push($arrayForPushing, $value);
				return $arrayForPushing;
            },
			'explodeTrimString' => function($string, $delimeter){
                $explodedString = explode($delimeter, trim ($string));
				$resultArray = [];
				foreach ($explodedString as $value) {
					if($value !== NULL && $value !== ''){
						array_push($resultArray, trim($value));
					}
				}
				return $resultArray;
            },
			'shuffleArray' => function($array){
				shuffle($array);
				return $array;
            },
        ];
    }
}
