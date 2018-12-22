<?php

class SitePaths
{
    private $langs;

    function __construct()
    {
        $this->langs = \Langs::getInstance();
    }

    public static function getSiteSubDomains()
    {
        $langs = [];
        $parentPagesReqToDb = \DataBase::justQueryToDataBase("SELECT id as id, text_id as textId, text, default_value as defaultValue  FROM site_sub_domains");
        while ($responseFromDb = \DataBase::responseFromDataBase($parentPagesReqToDb)) {
            $langs[] = $responseFromDb;
        }
        return $langs;
    }

    public function deleteSubDomain($id)
    {
        if (!$id) {
            throw new \SystemException('no_id', 'backend.errors.has_not_required_data', 'json');
        } else if ($this->isDefaultSubDomain($id)) {
            throw new \SystemException('could_not_delete_default_subdomain', 'backend.errors.could_not_delete_default_subdomain', 'json');
        } else if ($this->lengthSubDomains() < 2) {
            throw new \SystemException('could_not_delete_default_subdomain', 'backend.errors.could_not_delete_default_subdomain', 'json');
        }
        \DataBase::justQueryToDataBase("DELETE FROM site_sub_domains WHERE id = $id");
        \Response::goodResponse();
        return true;
    }

    public function updateSubDomain($id)
    {
        if (!$id) {
            throw new \SystemException('no_id', 'backend.errors.has_not_required_data', 'json');
        }
        if ($_POST["defaultValue"]) {
            $this->checkStatusDefaultDomain();
        }
        \DataBase::justQueryToDataBase("UPDATE site_sub_domains SET text_id='" . $_POST["textId"] . "', text='" . $_POST["text"] . "', default_value='" . $_POST["defaultValue"] . "' WHERE id = $id");
        \Response::goodResponse();
        return true;
    }

    private function checkStatusDefaultDomain()
    {
        \DataBase::justQueryToDataBase("UPDATE site_sub_domains SET default_value= 0 WHERE default_value= 1");
    }

    public function insertSubDomain($data)
    {
        $textId = $data["textId"];
        if ($this->isHavePrefixAtSubDomains($textId)) {
            $this->errorNotUniqPrefixHandler($textId);
            return false;
        }
        if ($_POST["defaultValue"]) {
            $this->checkStatusDefaultDomain();
        }
        \DataBase::justQueryToDataBase("INSERT site_sub_domains SET text_id='" . $textId . "', text='" . $data["text"] . "', default_value='" . $data["defaultValue"] . "'");
        \Response::goodResponse();
        return true;
    }

    public function getSubDomainForRequest()
    {
        $subDomain = \Requests::getSubDomain();
        $subDomainNameSiteMap = ($this->hasSubDomainTextId($subDomain)) ? $subDomain : $this->getDefaultSubDomainTextId();
        return $subDomainNameSiteMap;
    }

    public function getSubDomainIdForRequest()
    {
        $subDomainTextId = $this->getSubDomainForRequest();
        return $this->getSubDomainIdByTextId($subDomainTextId);
    }

    public function getSubDomainIdByTextId($subDomainTextId)
    {
        return \DataBase::queryToDataBase("SELECT id FROM site_sub_domains WHERE text_id= '$subDomainTextId'")["id"];
    }

    public function getPageSubDomainId($pageId)
    {
        return \DataBase::queryToDataBase("SELECT sub_domain FROM page_parent_id WHERE id= $pageId")["sub_domain"];
    }

    public static function getSitePath()
    {
        return \SitePaths::getProtocol() . \SitePaths::getRelativePath();
    }

    public static function getProtocol()
    {
        return stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https://' : 'http://';
    }

    public static function getRelativePath()
    {
        return \DataBase::queryToDataBase("SELECT url FROM site_url LIMIT 1")["url"];
    }

    public function getDefaultSubDomainTextId()
    {
        return \DataBase::queryToDataBase("SELECT * FROM site_sub_domains WHERE default_value = 1")["text_id"];
    }

    public function setDefaultSubDomainByTextId($textId)
    {
        \DataBase::justQueryToDataBase("UPDATE site_sub_domains SET default_value= 1 WHERE text_id= '$textId'");
    }

    public function hasSubDomainTextId($textId)
    {
        return !!\DataBase::queryToDataBase("SELECT * FROM site_sub_domains WHERE text_id = $textId")["id"];
    }

    private function errorNotUniqPrefixHandler($prefix)
    {
        \Response::errorResponse($prefix . " - " . $this->langs->getMessage("backend.errors.not_uniq_subdomain_prefix"));
    }

    private function isHavePrefixAtSubDomains($pefix)
    {
        return !!\DataBase::queryToDataBase("SELECT id FROM site_sub_domains WHERE text_id = '$pefix'")["id"];
    }

    private function isDefaultSubDomain($id)
    {
        return !!\DataBase::queryToDataBase("SELECT * FROM site_sub_domains WHERE id = $id AND default_value = 1")["id"];
    }

    private function lengthSubDomains()
    {
        return \DataBase::queryToDataBase("SELECT COUNT(*) FROM site_sub_domains")["COUNT(*)"];
    }
}