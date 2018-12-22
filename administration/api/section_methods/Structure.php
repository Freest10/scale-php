<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 15.02.2018
 * Time: 21:11
 */

namespace SectionApi;


class Structure implements SectionApi
{
    private $apiClassNames = ['Page', 'TemplatesTypeList', 'ChangeSortOrParentOfPage'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}