<?php

namespace SectionApi;


class Feedback implements SectionApi
{
    private $apiClassNames = ['Addresses', 'MailTemplates', 'Messages'];

    public function getArrayOfApiClassNames()
    {
        return $this->apiClassNames;
    }
}