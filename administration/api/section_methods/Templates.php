<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 15.02.2018
 * Time: 21:11
 */

namespace SectionApi;


class Templates implements SectionApi
{
    private $apiClassNames = ['TemplatesTypeList', 'FieldTypes', 'TemplatesType'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}