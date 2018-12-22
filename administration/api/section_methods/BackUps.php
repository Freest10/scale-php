<?php

namespace SectionApi;


class BackUps implements SectionApi
{
    private $apiClassNames = ['BackUps'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}