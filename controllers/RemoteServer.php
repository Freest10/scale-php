<?php

namespace Controller {
    class RemoteServer
    {
        private $config;

        function __construct()
        {
            $this->config = \MainConfiguration::getInstance();
        }

        public function getRemoteServerPath()
        {
            return $this->config->get('system', 'remote_server');
        }

    }
}