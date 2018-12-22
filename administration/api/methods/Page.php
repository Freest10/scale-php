<?php

namespace Api;
class Page
{
    private $pageController;

    function __construct()
    {
        $this->pageController = new \Controller\Page;
    }

    public function get($id)
    {
        if ($id) {
            $this->getPageById($id);
        } else {
            $this->getPages();
        }
    }

    public function put()
    {
        $_PUT = \Requests::getPUT();
        $this->pageController->setSubDomain($_PUT["subDomain"]);
        if($this->pageController->createPage($_PUT[id])){
            \Response::goodResponse();
        }
    }

    public function delete()
    {
        $_DELETE = \Requests::getDELETE();
        $this->pageController->deletePages($_DELETE["ids"]);
    }

    public function set($id)
    {
        $this->pageController->pageData = $_POST;
        $this->pageController->setPageById($id);
    }

    private function getPageById($id)
    {
        $this->pageController->getPageById($id);
    }

    private function getPages()
    {
        $subDomain = $_GET["subDomain"];
        if (!$subDomain) {
            throw new \SystemException('', 'backend.errors.there_is_not_subdomian', 'json');
            return false;
        }
        $treeView = new \Tree; // CONCAT('/#!/structure/',pgids.id) as href
        $treeView->jsTree("SELECT pgids.id as id, pgids.parent_id as parent_id, pgname.name as text
			FROM page_parent_id pgids 
			INNER JOIN page_id_name pgname 
			ON pgids.id = pgname.id 
			WHERE sub_domain = $subDomain
			ORDER BY sort ASC", \SitePaths::getSitePath()); //ORDER BY (parent_id ASC AND sort ASC)
        echo $treeView->jsonDecode();
    }
}