<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.07.2018
 * Time: 15:11
 */

namespace CustomMacros;


class PugAdapter
{
    private $prefix = 'customMacro_';
    private $macros;

    function __construct($macros)
    {
        $this->macros = $macros;
    }

    function getCustomMacros() {
        $macros = $this->macros;
        return [
            $this->prefix.'getTest' => function() use($macros){
                return $macros->getTest();
            }
        ];
    }
}