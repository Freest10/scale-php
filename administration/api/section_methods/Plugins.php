<?php

namespace SectionApi;


class Plugins implements SectionApi
{
    private $apiClassNames = ['PluginsInstalled', 'PluginsDownload', 'PluginRoutes', 'RemotePlugins'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}