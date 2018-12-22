<?php

namespace Api;
class Sitemap
{

    private $sitemap;

    function __construct()
    {
        $this->sitemap = \ClassesOperations::autoLoadClass('\Controller\SiteMap', '/controllers/Sitemap.php');
    }

    public function set()
    {
        $this->sitemap->updateSitemap();
    }
}