<?php

namespace Api;
class PluginsInstalled
{
    private $plugins;

    function __construct()
    {
        $this->plugins = \ClassesOperations::autoLoadClass('\Controller\Plugins', '/controllers/Plugins.php');
    }

    public function get()
    {
        $pluginRights = $this->plugins->getPlugins($_GET["begin"], $_GET['limit']);
        return \JsonOperations::printJsonFromPhp($pluginRights);
    }

    public function delete($textId)
    {
        $pluginRights = $this->plugins->removePlugin($textId);
        return \JsonOperations::printJsonFromPhp($pluginRights);
    }
}