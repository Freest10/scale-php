<?php

namespace Controller;
class Plugins
{
    private $langs;
    private $files;
    private $prefixPath = 'plugins';
    private $prefixPathRoutes = '!/plugins/plugin';
    private $prefixTabHref = '#';

    function __construct()
    {
        $this->langs = \Langs::getInstance();
        $this->files = \ClassesOperations::autoLoadClass('\FilesOperations', '/libs/systems_classes/files.php');
    }

    public function getPluginRights($userId, $begin, $limit)
    {
        $reqToPlugins = \DataBase::justQueryToDataBase("SELECT plugins.text_id as text_id,
          prights.read_right as read_right,
          prights.create_right as create_right,
          prights.edit_right as edit_right,
          prights.delete_right as delete_right
          FROM plugins as plugins
          LEFT JOIN plugins_rights prights ON (plugins.text_id = prights.text_id AND prights.user_id = $userId)
          LIMIT $begin,$limit");

        $plugins = [];
        $plugins["data"] = [];
        $plugins["limit"] = $limit;
        $plugins["begin"] = $begin;
        $plugins["total"] = $this->getTotalPlugins();

        while ($responseFromDb = \DataBase::responseFromDataBase($reqToPlugins)) {
            $data = [];
            $data["text_id"] = $responseFromDb["text_id"];
            $data["name"] = $this->langs->getPluginMessage($responseFromDb["text_id"], "name");
            $data["description"] = $this->langs->getPluginMessage($responseFromDb["text_id"], "description");
            $data["accesses"] = [];
            $data["accesses"]["read_right"] = $responseFromDb["read_right"] | 0;
            $data["accesses"]["create_right"] = $responseFromDb["create_right"] | 0;
            $data["accesses"]["edit_right"] = $responseFromDb["edit_right"] | 0;
            $data["accesses"]["delete_right"] = $responseFromDb["delete_right"] | 0;
            array_push($plugins["data"], $data);
        }
        return $plugins;
    }

    public function setPluginRights($userId, $data)
    {
        foreach ($data as $value) {
            if ($this->hasPluginRightsForUser($userId, $value['text_id'])) {
                $this->updateAccess($userId, $value['text_id'], $value['accesses']);
            } else {
                $this->insertAccess($userId, $value['text_id'], $value['accesses']);
            }
        }
    }

    public function getTotalPlugins()
    {
        $result = \DataBase::queryToDataBase("SELECT COUNT(plugins.text_id) as total FROM plugins")["total"];
        return $result ? $result : 0;
    }

    public function getPlugins($begin, $limit)
    {
        $reqToPlugins = $this->getLimitPlugins($begin, $limit);
        $plugins = [];
        $plugins["items"] = [];
        $plugins["limit"] = $limit;
        $plugins["begin"] = $begin;
        $plugins["total"] = $this->getTotalPlugins();

        while ($responseFromDb = \DataBase::responseFromDataBase($reqToPlugins)) {
            $data = [];
            $data["text_id"] = $responseFromDb["text_id"];
            $data["version"] = $responseFromDb["version"];
            $data["name"] = $this->langs->getPluginMessage($responseFromDb["text_id"], "name");
            $data["description"] = $this->langs->getPluginMessage($responseFromDb["text_id"], "description");
            array_push($plugins["items"], $data);
        }
        return $plugins;
    }

    public function removePlugin($textId)
    {
        $this->removePluginRights($textId);
        $this->removeFromPluginTable($textId);
        $pluginFilePath = CURRENT_WORKING_DIR . '/plugins/' . $textId;
        $this->files->deleteDirectory($pluginFilePath);
    }

    private function getLimitPlugins($begin, $limit)
    {
        return \DataBase::justQueryToDataBase("SELECT * FROM plugins LIMIT $begin,$limit");
    }

    public function getPluginRoutes()
    {
        $reqToPlugins = \DataBase::justQueryToDataBase("SELECT * FROM plugins");
        $sharePluginRoutes = [];
        $sharePluginRoutes["routeMethods"] = [];
        $sharePluginRoutes["routes"] = [];
        $sharePluginRoutes["tabs"] = [];
        $sharePluginRoutes["tabs"]["plugins"] = [];

        while ($responseFromDb = \DataBase::responseFromDataBase($reqToPlugins)) {
            $pluginName = $responseFromDb["text_id"];
            $pluginRoutes = $this->getRoutesDecodedJsonOfPlugin($pluginName);
            $proxiedRoutes = $this->getProxiedRoutesOfPlugin($pluginRoutes, $pluginName);
            $sharePluginRoutes["routeMethods"] = array_merge($sharePluginRoutes["routeMethods"], $this->getProxiedRouteMethods($proxiedRoutes->routeMethods, $pluginName));
            $sharePluginRoutes["routes"] = array_merge($sharePluginRoutes["routes"], $proxiedRoutes->routes);
            $sharePluginRoutes["tabs"]["plugins"][$pluginName] = $proxiedRoutes->tabs->plugins;
        }

        return $sharePluginRoutes;
    }

    public function installRemotePlugin($text_id, $version, $dumpFilePath)
    {
        $this->setPluginToDbq($text_id, $version);
        $backUpModule = \ClassesOperations::autoLoadClass('\Controller\BackUps', '/controllers/BackUps.php');
        $backUpModule->deployRemoteZipDump($dumpFilePath);
    }

    public function updateRemotePlugin($text_id, $version, $dumpFilePath)
    {
        $this->updatePluginToDbq($text_id, $version);
        $backUpModule = \ClassesOperations::autoLoadClass('\Controller\BackUps', '/controllers/BackUps.php');
        $backUpModule->deployRemoteZipDump($dumpFilePath);
    }

    private function getProxiedRouteMethods($routeMethods, $pluginName)
    {
        $methods = [];
        foreach ($routeMethods as $key => $value) {
            $methods[$key] = $routeMethods->$key;
            $methods[$key]->filePath = "plugins/$pluginName/$value->filePath";
            $methods[$key]->pluginName = $pluginName;
        }

        return $methods;
    }

    private function getRoutesDecodedJsonOfPlugin($name)
    {
        $jsonString = $this->files->readFile($this->prefixPath . '/' . $name . '/routes.json', true);
        return $jsonString ? json_decode($jsonString) : null;
    }

    private function getProxiedRoutesOfPlugin($routes, $pluginName)
    {
        $routes->routes = $this->getProxiedRoutes($routes->routes);
        if ($routes->tabs) {
            $routes->tabs->plugins = $this->getProxiedTabs($routes->tabs, $pluginName);
        }

        return $routes;
    }

    private function getProxiedRoutes($routes)
    {
        $proxiedRoutes = [];
        foreach ($routes as $key => $value) {
            $proxiedRoutes[$this->prefixPathRoutes . '/' . $key] = $value;
        }

        return $proxiedRoutes;
    }

    private function getProxiedTabs($tabs, $pluginName)
    {
        if (!($tabs && $tabs->plugins)) return null;

        $proxiedTabs = [];
        foreach ($tabs->plugins as $key => $value) {
            $proxiedTabs[$key] = $this->getProxiedPluginTabGroup($value, $pluginName);
        }

        return $proxiedTabs;
    }

    private function getProxiedPluginTabGroup($tabGroup, $pluginName)
    {
        $proxiedTabs = [];
        foreach ($tabGroup as $key => $value) {
            $proxiedTabs[$key] = [];
            $proxiedTabs[$key]['name'] = $this->langs->getPluginMessage($pluginName, $value->name);
            $proxiedTabs[$key]['href'] = $this->prefixTabHref . $this->prefixPathRoutes . '/' . $value->href;
        }

        return $proxiedTabs;
    }

    private function removeFromPluginTable($textId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM plugins WHERE text_id= '$textId'");
    }

    private function removePluginRights($textId)
    {
        \DataBase::justQueryToDataBase("DELETE FROM plugins_rights WHERE text_id= '$textId'");
    }

    private function updateAccess($id, $textId, $accesses)
    {
        \DataBase::queryToDataBase("UPDATE plugins_rights SET read_right=" . $accesses['read_right'] . ", edit_right=" . $accesses['edit_right'] . ", create_right=" . $accesses['create_right'] . ", delete_right=" . $accesses['delete_right'] . " WHERE user_id= $id AND text_id= '$textId'");
    }

    private function insertAccess($id, $textId, $accesses)
    {
        \DataBase::queryToDataBase("INSERT plugins_rights SET read_right=" . $accesses['read_right'] . ", edit_right=" . $accesses['edit_right'] . ", create_right=" . $accesses['create_right'] . ", delete_right=" . $accesses['delete_right'] . ", user_id= $id, text_id= '$textId'");
    }

    private function hasPluginRightsForUser($id, $textId)
    {
        return \DataBase::queryToDataBase("SELECT * FROM plugins_rights WHERE user_id= $id AND text_id= '$textId'") ? true : false;
    }

    private function setPluginToDbq($text_id, $version)
    {
        return \DataBase::queryToDataBase("INSERT INTO plugins SET text_id='$text_id', version='$version'");
    }

    private function updatePluginToDbq($text_id, $version)
    {
        return \DataBase::queryToDataBase("UPDATE plugins SET text_id='$text_id', version='$version'");
    }
}
