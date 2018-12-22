<?php

namespace Proxy;
class Admin
{
    public function init()
    {
        require_once CURRENT_WORKING_DIR . '/libs/proxy/import_libs/administration.php';
        header('Content-Type: text/html; charset=UTF-8');
    }
}