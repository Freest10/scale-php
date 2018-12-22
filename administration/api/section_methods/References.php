<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 15.02.2018
 * Time: 21:11
 */

namespace SectionApi;


class References implements SectionApi
{
    private $apiClassNames = ['References', 'ReferenceDatas', 'ReferenceElement'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}