<?php

namespace Api;
class PluginRoutes
{
    private $plugins;

    function __construct()
    {
        $this->plugins = \ClassesOperations::autoLoadClass('\Controller\Plugins', '/controllers/Plugins.php');
    }

    public function get()
    {
        $pluginRoutes = $this->plugins->getPluginRoutes();
        return \JsonOperations::printJsonFromPhp($pluginRoutes);
    }
}