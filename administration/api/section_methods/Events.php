<?php

namespace SectionApi;


class Events implements SectionApi
{
    private $apiClassNames = ['Events'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}