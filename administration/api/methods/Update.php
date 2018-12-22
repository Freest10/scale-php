<?php

namespace Api;


class Update
{

    private $remoteServer;
    private $remoteServerPath;
    private $system;

    function __construct()
    {
        $this->remoteServer = \ClassesOperations::autoLoadClass('\Controller\RemoteServer', '/controllers/RemoteServer.php');
        $this->system = \ClassesOperations::autoLoadClass('\Controller\System', '/controllers/System.php');
        $this->remoteServerPath = $this->remoteServer->getRemoteServerPath();
    }

    public function get()
    {
        try {
            $url = $this->remoteServerPath . '/update/?version=' . $_GET['version'];
            $options = array(
                'http' => array(
                    'header' => "Content-type: text/json",
                    'method' => 'GET'
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            if ($result) {
                $pluginData = json_decode($result);
                $version = $pluginData->lastVersion;
            }

            if (version_compare($version, $this->system->getSystemVersion(), '>')) {
                \Response::goodResponse();
            } else {
                \Response::errorResponse(null, 404);
            }

        } catch (\Exception $ex) {
            \Response::errorResponse(null, 503);
        }
    }

    public function set()
    {
        try {
            $url = $this->remoteServerPath . '/update';
            $postData = http_build_query(
                array(
                    'version' => $this->system->getSystemVersion()
                )
            );

            $options = array(
                'http' => array(
                    'header' => "Content-type: text/json",
                    'method' => 'POST',
                    'content' => $postData
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            if ($result) {
                $pluginData = json_decode($result);
                $version = $pluginData->version;
                $path = $pluginData->pathToFile;
                $fileFullPath = \FilesOperations::getFileFullPathFromRemoteServer($path);
                $this->system->updateSystem($version, $fileFullPath);
            }

            \Response::goodResponse();
        } catch (\Exception $ex) {
            \Response::errorResponse(null, 503);
        }
    }
}