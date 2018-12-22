<?php

namespace Api;
class PluginsDownload
{
    private $plugins;

    function __construct()
    {
        $this->plugins = \ClassesOperations::autoLoadClass('\Controller\Plugins', '/controllers/Plugins.php');
    }

    public function get()
    {

    }
}