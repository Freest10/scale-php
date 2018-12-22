<?php

namespace SectionApi;


class Settings implements SectionApi
{
    private $apiClassNames = ['Settings', 'SearchIndex', 'Sitemap', 'Robots', 'SubDomains', 'AboutProgram', 'Update'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}