<?php

namespace PageInfo;

include_once CURRENT_WORKING_DIR . '/controllers/Page.php';
include_once CURRENT_WORKING_DIR . '/libs/root-src/data-base.php';
include_once CURRENT_WORKING_DIR . '/controllers/ClientStructData.php';


use Controller\ClientStructData as ClientStructData;
use Controller\Page as Page;
use Controller\Users as Users;


class PageInfo
{

    private $page_info;

    public function getPageInfo()
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $this->page_info = [];
        $userModel = new Users();
        $this->page_info["userId"] = $userModel->getUserId();
        $this->page_info["page"] = [];
        $this->setFullPath();
        $this->setPageIdByFullPath($this->page_info["page"]["url"]);
        $this->setParentId($this->page_info["page"]["id"]);
        $this->setParentPages($this->page_info["page"]["id"]);
        if (Page::getActive($this->page_info["page"]["id"])) {
            $this->setMainPageData();
            $this->page_info['groups'] = $this->getGroupsAndPropsInThem($this->page_info['page']['id'], $this->page_info['page']['typeId']);

        } else {
            $this->page_info["page"]['name'] = "Not found";
            $this->page_info["page"]['typeId'] = "not_found";
        }

        return $this->page_info;
    }

    public function getPageInfoById($id)
    {
        include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
        $this->page_info = [];
        $userModel = new Users();
        $this->page_info["userId"] = $userModel->getUserId();
        $this->page_info["page"] = [];
        $this->setFullPath();
        $this->page_info["page"]["id"] = $id;
        $this->setParentId($this->page_info["page"]["id"]);
        $this->setParentPages($this->page_info["page"]["id"]);
        if (Page::getActive($this->page_info["page"]["id"])) {
            $this->setMainPageData();
            $this->page_info['groups'] = $this->getGroupsAndPropsInThem($this->page_info['page']['id'], $this->page_info['page']['typeId']);
        } else {
            $this->page_info["page"]['name'] = "Not found";
            $this->page_info["page"]['typeId'] = "not_found";
        }
        return $this->page_info;
    }

    private function setFullPath()
    {
        $this->page_info["page"]["url"] = $this->getFullPath();
    }

    private function setPageIdByFullPath($full_path)
    {
        $this->page_info["page"]["id"] = Page::getPageIdByFullPath($full_path, true);
    }

    private function getFullPath()
    {
        $explParamsFromUri = explode("?", $_SERVER['REQUEST_URI']);//отсеиваем параметры
        $full_path = $explParamsFromUri[0];
        if (substr($full_path, -1) != '/') $full_path .= '/';
        return $full_path;
    }

    private function setMainPageData()
    {
        $this->page_info['page']['name'] = Page::getPageName($this->page_info["page"]["id"]);
        $dop_properties_page = Page::getDopPropertiesPageById($this->page_info["page"]["id"]);
        $this->page_info['page']['description'] = $dop_properties_page['description'];
        $this->page_info['page']['title'] = $dop_properties_page['title'];
        $this->page_info['page']['h1'] = $dop_properties_page['h1'];
        $this->page_info['page']['typeId'] = Page::getTypePageById($this->page_info["page"]["id"]);
    }

    private function getGroupsAndPropsInThem($page_id, $template_id)
    {
        $client_struct_data = new ClientStructData();
        $client_struct_data->structId = $page_id;
        $client_struct_data->templateId = $template_id;
        return $client_struct_data->getGroupsWithIncludeProperties();
    }

    private function setParentPages($id)
    {
        $page_model = new Page();
        $this->page_info["parents"] = $page_model->getParentPagesById($id);
    }

    private function setParentId($id)
    {
        $this->page_info['page']['parentId'] = Page::getParentIdById($id);
    }

    public function getParams()
    {
        $params = [];
        $params["get"] = $_GET;
        $params["post"] = $_POST;
        $params["request"] = json_decode(file_get_contents('php://input'), true);;
        return $params;
    }

    public function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
}