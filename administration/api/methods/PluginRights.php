<?php

namespace Api;
class PluginRights
{
    private $sectionsController;
    private $users;

    function __construct()
    {
        $this->plugins = \ClassesOperations::autoLoadClass('\Controller\Plugins', '/controllers/Plugins.php');
        $this->users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
    }

    public function set($id)
    {
        $this->plugins->setPluginRights($id, $_POST['data']);
        \Response::goodResponse();
    }

    public function get($id)
    {
        $result = [];
        $userName = $this->users->getUserName($id);
        $result['name'] = $userName;
        $result['id'] = $id;
        $pluginRights = $this->plugins->getPluginRights($id, $_GET["begin"], $_GET['limit']);
        $result['plugin_rights'] = $pluginRights['data'];
        $result['limit'] = $pluginRights['limit'];
        $result['begin'] = $pluginRights['begin'];
        $result['total'] = $pluginRights['total'];
        return \JsonOperations::printJsonFromPhp($result);
    }
}