<?php

namespace Api {
    class SubDomains extends \SitePaths
    {
        public function get()
        {
            $this->getAllSubDomains();
        }

        public function delete()
        {
            $_DELETE = \Requests::getDELETE();
            $this->deleteSubDomain($_DELETE["id"]);
        }

        public function set($id)
        {
            $this->updateSubDomain($id);
        }

        public function put()
        {
            $_PUT = \Requests::getPUT();
            $this->insertSubDomain($_PUT);
        }

        public function getAllSubDomains()
        {
            \JsonOperations::printJsonFromPhp($this->getSiteSubDomains());
        }
    }
}