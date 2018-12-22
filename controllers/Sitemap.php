<?php

namespace Controller;
class SiteMap
{
    private $files;
    private $sitePaths;
    private $page;
    private $url;
    private $pagesPerSitemap;

    public function updateSitemap()
    {
        $this->sitePaths = \ClassesOperations::autoLoadClass('\SitePaths', '/libs/root-src/SitePaths.php');
        $this->page = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
        $this->files = \ClassesOperations::autoLoadClass('\FilesOperations', '/libs/systems_classes/files.php');

        $this->files->clearFolder("/sitemap/parts");
        $this->files->clearFolder("/sitemap/sitemap_index");
        $url = $this->sitePaths->getRelativePath();
        $this->url = substr($url, 0, -1);
        $subDomainIds = $this->sitePaths->getSiteSubDomains();
        $config = \MainConfiguration::getInstance();
        $this->pagesPerSitemap = $config->get('system', 'sitemap_pages');
        if (!$this->pagesPerSitemap) {
            $this->pagesPerSitemap = "500";
        }

        foreach ($subDomainIds as $value) {
            $urlXml = ($value['defaultValue'] == 1) ? $this->sitePaths->getProtocol() . $this->url : $this->sitePaths->getProtocol() . $value['textId'] . '.' . $this->url;
            $this->rendrerSitemapForSubDomain($value['id'], $value['textId'], $urlXml);
        }

        \Response::goodResponse();
    }

    private function rendrerSitemapForSubDomain($subDomainId, $subDomainTextId, $urlXml)
    {
        $total = $this->page->getCountPagesForSubDomain($subDomainId);
        $filesCount = ceil($total / $this->pagesPerSitemap);
        $this->createSitemapIndex($subDomainTextId, $filesCount, $urlXml);
        $this->createSitemapParts($subDomainTextId, $subDomainId, $filesCount, $urlXml);
    }

    private function createSitemapIndex($subDomainTextId, $filesCount, $urlXml)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument();
        $xml->startElement("sitemapindex");
        $xml->writeAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
        $this->getSitemapIndexXml($xml, $filesCount, $subDomainTextId, $urlXml);
        $xml->endElement();
        $this->files->writeToFile("sitemap/sitemap_index/" . $subDomainTextId . "_sitemap.xml", $xml->outputMemory());
    }

    private function createSitemapParts($subDomainTextId, $subDomainId, $filesCount, $urlXml)
    {
        for ($i = 1; $i <= $filesCount; $i++) {
            $this->createSitemapPart($i, $subDomainTextId, $subDomainId, $urlXml);
        }
    }

    private function createSitemapPart($num, $subDomainTextId, $subDomainId, $urlXml)
    {
        $numCount = intval((int)($num === 1) ? 0 : $num);
        $begin = ($numCount === 0) ? $numCount : ($numCount - 1) * $this->pagesPerSitemap;
        $pageReq = $this->page->getPagesReqForSubDomain($subDomainId, $begin, intval($this->pagesPerSitemap));
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument();
        $xml->startElement("urlset");
        $xml->writeAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
        while ($responseFromDb = \DataBase::responseFromDataBase($pageReq)) {
            $xml->startElement("url");
            $xml->writeElement("loc", $urlXml . $responseFromDb['path']);
            $xml->writeElement("lastmod", $responseFromDb['last_mod']);
            $xml->writeElement("priority", "0.2");
            $xml->endElement();
        }

        $xml->endElement();
        $folderPath = "sitemap/parts/" . $subDomainTextId;
        $this->files->createFolders("/" . $folderPath);
        $this->files->writeToFile($folderPath . "/part" . $num . ".xml", $xml->outputMemory());
    }

    private function getSitemapIndexXml($xml, $filesCount, $subDomainTextId, $urlXml)
    {
        for ($i = 1; $i <= $filesCount; $i++) {
            $xml->startElement("sitemap");
            $xml->writeElement("loc", $urlXml . "/sitemap/parts/" . $subDomainTextId . "/part" . $i . ".xml");
            $xml->endElement();
        }
    }
}