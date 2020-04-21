<?php

namespace Macros;

use Controller\Basket as Basket;
use Controller\Emarket as Emarket;
use Controller\Messages as Messages;
use Controller\Page as Page;
use Controller\References as References;
use Controller\Search as Search;
use Controller\StructData as StructData;
use Controller\TemplatesType as TemplatesType;
use Controller\Users as Users;
use Requests;
use SitePaths;
use ThumbnailImage;

class Macros
{

    public function getPages($options)
    {
        $page = new Page();
        return $page->getChildPages($options);
    }
	
	public function getTotalChildPages($options)
    {
        $page = new Page();
        return $page->getTotalChildPages($options);
    }
	
    public function createPage($parentId = 0, $pageName, $templateId = null)
    {
        $sitePaths = new \SitePaths();
        $page = new Page();
        $page->setSubDomain($sitePaths->getSubDomainIdForRequest());
        return $page->createPage($parentId, $pageName, $templateId);
    }

    public function getPagesById($options)
    {
        $page = new Page();
        return $page->getPagesByIdForClient($options);
    }

    public function changePageType($pageId, $typeId)
    {
        $page = new Page();
        return $page->setPageTemplateId($pageId, $typeId);
    }

    public function changePageName($pageId, $pageName)
    {
        $page = new Page();
        $page->setPageName($pageId, $pageName);
    }
	
	public function getMinMaxValue($options)
    {	
        $page = new Page();
        return $page->getMinMaxValue($options);
    }

    //$propName: 'active' | 'url' | 'h1'  | 'title' | 'description' | 'noIndex'
    public function changeGeneralFieldOfPage($pageId, $propName, $value)
    {
        $structData = new StructData();
        $structData->updateGeneralFieldsById($pageId, $propName, $value);
    }

    public function updateFieldPage($id, $fieldTextId, $value)
    {
        $structData = new StructData();
        $structData->setFieldValueByPageId($id, $fieldTextId, $value);
    }

    public function thumbnail($url, $options)
    {
        include_once CURRENT_WORKING_DIR . '/thumbnail/index.php';
        $thumb = new ThumbnailImage();
        $thumb->setOptions($options);
        return $thumb->doThumbnail($url);
    }

    public function getTypes($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/TemplatesType.php';
        $type_model = new TemplatesType();
        return $type_model->getTypeForClient($options);
    }

    public function getReferences($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/References.php';
        $ref_model = new References();
        return $ref_model->getReferencesDataForClient($options);
    }

    public function getUsers($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $user_model = new Users();
        return $user_model->getUsersForClient($options);
    }

    public function getOrdersForUsers($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Emarket.php';
        $emarket = new Emarket();
        return $emarket->getOrdersForUsers($options);
    }

    public function getMessage($id)
    {
        if ($id) {
            include_once CURRENT_WORKING_DIR . '/controllers/Messages.php';
            $messages = new Messages();
            return $messages->getClientMessages($id);
        }

        return null;
    }

    public function sendMessage($id, $value)
    {
        if ($id) {
            include_once CURRENT_WORKING_DIR . '/controllers/Messages.php';
            $messages = new Messages();
            return $messages->sendClientMessages($id, $value);
        }

        return null;
    }

    public function addToBasket($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Basket.php';
        $basket = new Basket();
        $basket->addToBasket($options);
    }

    public function updateBasket($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Basket.php';
        $basket = new Basket();
        $basket->updateBasket($options);
    }

    public function getBasket()
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Basket.php';
        $basket = new Basket();
        return $basket->getBasket();
    }

    public function setOrder($values)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Emarket.php';
        $emarket = new Emarket();
        return $emarket->setOrderClient($values);
    }

    public function getLastOrder()
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Emarket.php';
        $emarket = new Emarket();
        return $emarket->getLastOrder();
    }

    public function search($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Search.php';
        $search = new Search();
        return $search->searchPages($options);
    }

    public function getPath()
    {
        include_once CURRENT_WORKING_DIR . '/libs/root-src/requests.php';
        $request = new Requests();
        $reqArr = $request->getUriMassive();
        array_shift($reqArr);
        return $reqArr;
    }

    public function getBreadCrumbs()
    {
        $page = new Page();
        return $page->getBreadCrumbs();
    }

    public function removeUser($id)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $usersModel = new Users();
        $usersModel->deleteUser($id, true);
        return true;
    }

    public function createUser($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $usersModel = new Users();
        return $usersModel->createUserClient($options);
    }

    public function editUser($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $usersModel = new Users();
        return $usersModel->editUserClient($options);
    }

    public function logOutUser()
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $usersModel = new Users();
        return $usersModel->logOutUser();
    }

    public function authUser($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $usersModel = new Users();
        return $usersModel->authUser($options);
    }

    public function restoreUser($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $usersModel = new Users();
        return $usersModel->restoreUser($options);
    }

    public function passwordUserRecovery($options)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $usersModel = new Users();
        return $usersModel->setRecoveryPasswordToUser($options);
    }

    public function isMobile()
    {
        return boolval(
            preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"])
        );
    }

    public function setNotFoundHeader()
    {
        header("HTTP/1.0 404 Not Found");
        exit();
    }
	
	public function roundHalfToDown($number, $precision = 0)
    {
        return round($number, $precision, PHP_ROUND_HALF_DOWN);
    }
	
	public function setJsonResponseHeaderContentType()
    {
        header('Content-Type: application/json');
    }
	
	public function isEmptyArray($array)
    {
        return isset($array) ? count($array) == 0 : true;
    }
	
	public function isThereProperty($object, $propertyName)
    {	
		return isset($object[$propertyName]);
    }
	
	public function setAccessControlAllowOrigin() {
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			// Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
			// you want to allow, and if so:
			header("Access-Control-Allow-Origin: *");
			header('Access-Control-Allow-Credentials: true');
		}

		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
				// may also be using PUT, PATCH, HEAD etc
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
				header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
		}
	}

    public function setResponseHeaderCode($code)
    {
        http_response_code($code);
        exit();
    }

    public function getActiveSubDomain()
    {
        return \Requests::getSubDomain();
    }

    public function getSubDomains()
    {
        include_once CURRENT_WORKING_DIR . '/libs/root-src/SitePaths.php';
        return SitePaths::getSiteSubDomains();
    }
}

