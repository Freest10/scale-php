<?php

class ProxyPath
{

    private $uri_massive;
    private $securityLib;
    private $paths = [];
    private $dataBase;
    private $config;

    function __construct()
    {
        $this->uri_massive = \Requests::getUriMassive();
        $this->dataBase = new \DataBase();
        $this->config = MainConfiguration::getInstance();
        //Filter req data
        $this->securityLib = new Security();
        $this->securityLib->secureReq();
    }

    public function addPath($path, $className)
    {
        $this->paths[$path] = $className;
    }

    public function initPath()
    {
        if ($this->isHaveCSRF() && $this->config->get("security", "csrf")) {
            throw new \SystemException('', 'backend.errors.csrf', 'modal');
        }

        if ($this->paths[$this->uri_massive[1]]) {
            $this->paths[$this->uri_massive[1]]->init();
        } else {
            require CURRENT_WORKING_DIR . '/libs/proxy/import_libs/client.php';
            include_once CURRENT_WORKING_DIR . '/client/index.php';
            $mainTemplate = new ClientTemplate();
            $mainTemplate->renderClient();
        }
    }

    public function childRoutes($parent, $path, $page)
    {

    }

    private function isHaveCSRF()
    {
        return ($_SERVER["REQUEST_METHOD"] != "GET" && $this->isWrongRefferer($_SERVER["HTTP_REFERER"])) ? true : false;
    }

    private function isWrongRefferer($referer)
    {
        if (!$referer) {
            return false;
        }
        preg_match('/^(?:https?:\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:\/\n]+)/im', $referer, $matches);
        $sitePath = SitePaths::getRelativePath();
        $matchedFromRefferer = $matches[1];
        if ($sitePath === $matchedFromRefferer) {
            return false;
        }

        $hostName = $this->getHostNameFromUri($sitePath);
        $hostNameRefferer = $this->getHostNameFromUri($matches[1]);
        $badRef = true;
        if ($hostName === $hostNameRefferer) {
            $badRef = false;
        }

        return $badRef;
    }

    private function getHostNameFromUri($uri)
    {
        $sitePathExploded = explode('.', $uri);
        $hostPosition = count($sitePathExploded) - 2;
        return $sitePathExploded[$hostPosition];
    }
}