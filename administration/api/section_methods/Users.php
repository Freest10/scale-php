<?php

namespace SectionApi;


class Users implements SectionApi
{
    private $apiClassNames = ['Users', 'MainRights', 'PluginRights'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}