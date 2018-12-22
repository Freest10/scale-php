<?php

namespace Api;

use \Controller\TemplatesType as TemplatesType;

class TemplatesTypeList
{
    public function get()
    {
        $jsonOperations = \ClassesOperations::autoLoadClass('\JsonOperations', '/libs/systems_classes/JsonOperations.php');
        $jsonOperations->printJsonFromPhp(TemplatesType::getTemplatesList());
    }
}