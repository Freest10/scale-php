<?php

namespace Api;


class BackUps
{

    private $backUps;

    function __construct()
    {
        $this->backUps = \ClassesOperations::autoLoadClass('\Controller\BackUps', '/controllers/BackUps.php');
    }

    public function get()
    {
        $this->backUps->createBackUp($_GET);
    }

    public function set()
    {
        $this->backUps->deployBackUp($_POST);
        \Response::goodResponse();
    }
}