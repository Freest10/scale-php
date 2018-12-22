<?php
require CURRENT_WORKING_DIR . '/libs/root-src/data-base.php';
require CURRENT_WORKING_DIR . '/libs/langs/index.php';
require CURRENT_WORKING_DIR . '/libs/root-src/exception/index.php';
require CURRENT_WORKING_DIR . '/libs/proxy/index.php';
require CURRENT_WORKING_DIR . '/libs/root-src/SitePaths.php';
require CURRENT_WORKING_DIR . '/libs/root-src/Requests.php';

try {
        $dataBase = new \DataBase;
        $dataBase->connectToDataBase();
        $proxy = new ProxyPath();
        $proxy->addPath('admin', new \Proxy\Admin());
        $proxy->addPath('sitemap.xml', new \Proxy\Sitemap());
        $proxy->initPath();
        $dataBase->closeConnectToDataBase();
} catch (\SystemException $e) {
    $e->showMessage();
}