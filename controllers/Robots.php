<?php
namespace Controller;
	class Robots {

	    private $files;

        function __construct() {
           $this->files = \ClassesOperations::autoLoadClass('\FilesOperations', '/libs/systems_classes/files.php');
        }

		public function getRobots(){
            $data = [];
            $data["data"]= $this->files->readFile("robots.txt", true);
            \JsonOperations::printJsonFromPhp($data);
		}

		public function setRobots($data){
		    if($data){
               $isHaveSitemapString = stripos($data, "Sitemap");
               if($isHaveSitemapString === false){
                $data .= "\r";
                $data .= "Sitemap: ";
                $data .= $this->getFulPath() . '/';
                $data .= "sitemap.xml";
               }
		       $this->files->writeToFile("robots.txt", $data);
		    }
		    \Response::goodResponse();
        }

        private function getFulPath(){
            return \DataBase::queryToDataBase("SELECT url FROM site_url WHERE id=1")["url"];
        }
	}