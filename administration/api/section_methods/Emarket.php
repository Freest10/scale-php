<?php

namespace SectionApi;


class Emarket implements SectionApi
{
    private $apiClassNames = ['Emarket'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}