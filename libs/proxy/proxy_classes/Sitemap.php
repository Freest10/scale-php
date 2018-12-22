<?php
namespace Proxy;

class Sitemap
{
    public function init()
    {
        require_once CURRENT_WORKING_DIR . '/libs/proxy/import_libs/Sitemap.php';
        $sitePaths = new \SitePaths();
        $subDomainNameSiteMap = $sitePaths->getSubDomainForRequest();
        $this->showSiteMapFileBySubDomain($subDomainNameSiteMap);
    }

    private function showSiteMapFileBySubDomain($subDomainNameSiteMap)
    {
        $filesOperations = new \FilesOperations();
        $filePath = 'sitemap/sitemap_index/'.$subDomainNameSiteMap.'_sitemap.xml';
        $sitemapContent = $filesOperations->readFile($filePath, true);
       header ("Content-Type:text/xml");
       echo($sitemapContent);
    }
}