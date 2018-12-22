<?php

namespace Controller;

class System
{
    private $files;
    private $aboutProgramFilePath = '/libs/root-src/about_program.json';

    function __construct()
    {
        $this->files = \ClassesOperations::autoLoadClass('\FilesOperations', '/libs/systems_classes/files.php');
    }

    public function getSystemVersion()
    {
        $aboutProgramJson = $this->files->readJsonFileAsPhp($this->aboutProgramFilePath);
        return $aboutProgramJson->version;
    }

    public function updateSystem($version, $dumpFilePath)
    {
        $this->updateAboutProgramFile($version);
        $backUpModule = \ClassesOperations::autoLoadClass('\Controller\BackUps', '/controllers/BackUps.php');
        $backUpModule->deployRemoteZipDump($dumpFilePath);
    }

    private function updateAboutProgramFile($version)
    {
        $aboutProgramJson = $this->files->readJsonFileAsPhp($this->aboutProgramFilePath);
        $aboutProgramJson->version = $version;
        $aboutProgramJson->date_update = date("d.m.Y");
        $this->files->writePhpAsJsonFile($this->aboutProgramFilePath, $aboutProgramJson);
    }
}