<?php

namespace Api {
    class RemotePlugins
    {
        private $remoteServer;
        private $remoteServerPath;
        private $plugin;
        private $langs;

        function __construct()
        {
            $this->remoteServer = \ClassesOperations::autoLoadClass('\Controller\RemoteServer', '/controllers/RemoteServer.php');
            $this->plugin = \ClassesOperations::autoLoadClass('\Controller\Plugins', '/controllers/Plugins.php');
            $this->langs = \ClassesOperations::autoLoadClass('\LangsService', '/libs/langs/LangsService.php');
            $this->remoteServerPath = $this->remoteServer->getRemoteServerPath();
        }

        public function get()
        {
            try {
                $activeLang = $this->langs->getActiveLangData()["text_id"];
                $requestStringParams = "?lang=$activeLang";
                if (isset($_GET['limit']) && isset($_GET['begin'])) {
                    $requestStringParams .= "&limit=" . $_GET['limit'] . "&begin=" . $_GET['begin'];
                }

                $response = \file_get_contents($this->remoteServerPath . '/plugins' . $requestStringParams);
                echo($response);
            } catch (\Exception $ex) {
                \Response::errorResponse(null, 503);
            }
        }

        public function put($textId)
        {
            $this->getPluginJsonFromRemoteServer($textId, 'install');
        }

        public function set($textId)
        {
            $this->getPluginJsonFromRemoteServer($textId, 'update');
        }

        private function getPluginJsonFromRemoteServer($textId, $type)
        {
            try {
                $url = $this->remoteServerPath . '/plugins' . '/' . $textId;
                $options = array(
                    'http' => array(
                        'header' => "Content-type: text/json",
                        'method' => $type === 'install' ? 'PUT' : 'POST'
                    )
                );
                $context = stream_context_create($options);
                $result = file_get_contents($url, false, $context);
                if ($result) {
                    $pluginData = json_decode($result);
                    $name = $pluginData->text_id;
                    $version = $pluginData->version;
                    $path = $pluginData->pathToFile;
                    $fileFullPath = \FilesOperations::getFileFullPathFromRemoteServer($path);
                }

                if ($type === 'install') {
                    $this->plugin->installRemotePlugin($name, $version, $fileFullPath);
                } else if ($type === 'update') {
                    $this->plugin->updateRemotePlugin($name, $version, $fileFullPath);
                }

                \Response::goodResponse();
            } catch (\Exception $ex) {
                \Response::errorResponse(null, 503);
            }
        }
    }
}