<?php

namespace Controller;

require_once CURRENT_WORKING_DIR . '/backups/mysql_dump/mysqldump.php';

use Ifsnop\Mysqldump as IMysqldump;

class BackUps
{
    private $zip;
    private $dumpFileName;
    private $dumpFilePath;
    private $dumpFullPath;
    private $zipFiles = [];
    private $copiedArchiveName;
    private $dontTouchFiles = [];
    private $removeFiles = [];
    private $configIni = [];
    private $dataBaseTextId;
    private $excludeExtract = ['system.json', 'dump.sql'];

    function __construct()
    {
        $this->dumpFileName = 'dump.sql';
        $this->dataBaseTextId = 'data_base';
        $this->dumpFilePath = 'backups/export/' . $this->dumpFileName;
        $this->dumpFullPath = CURRENT_WORKING_DIR . '/' . $this->dumpFilePath;
    }

    public function createBackUp($data)
    {
        $this->zip = new \ZipArchive();
        $zipFileName = "backup_" . date("Y-m-d_H-i-s") . ".zip";
        $filename = CURRENT_WORKING_DIR . "/backups/export/{$zipFileName}";
        if ($this->zip->open($filename, \ZipArchive::CREATE) !== TRUE) {
            exit();
        }

        foreach ($data['data'] as $value) {
            if ($value['checked'] == 1) {
                if ($value['path'] && $value['showPath'] == 1) {
                    $this->addToBackUpsByPath($value['path'], $value['textId']);
                } else if ($value['textId'] == $this->dataBaseTextId) {
                    $this->addToBackUpsDataBase();
                }
            }
        }

        $this->addAboutProgramFileToBackUp();
        $this->zip->close();
        unlink($this->dumpFullPath);
        $this->printBackUpZipFile($zipFileName);
    }

    public function deployBackUp($data)
    {
        $this->zip = new \ZipArchive();
        $archivePath = CURRENT_WORKING_DIR . $data['filePath'];
        if ($this->zip->open($archivePath) === TRUE) {
            $this->setBackUpZipFiles();
            foreach ($data['data'] as $value) {
                if ($value['checked'] == 1) {
                    if ($value['textId'] === $this->dataBaseTextId) {
                        $this->deployDataBase(true);
                    } else {
                        $this->deployBackUpsByPath($value['path'], $value['textId']);
                    }
                }
            }

            $this->zip->close();
        } else {
            throw new \SystemException('', 'backend.errors.could_not_open_zip', 'json');
        }
    }

    public function deployRemoteZipDump($dumpFilePath)
    {
        $this->copyArchiveFromRemoteServer($dumpFilePath);
        $this->zip = new \ZipArchive();
        $zipPath = CURRENT_WORKING_DIR . '/' . $this->copiedArchiveName;
        if ($this->zip->open($zipPath) === TRUE) {
            $this->setBackUpZipFilesBySystemJson();
            $this->setSystemJsonValuesInZip();
            foreach ($this->zipFiles as $value) {
                if ($this->couldExtractFile($value) && !in_array($value, $this->excludeExtract)) {
                    $this->copyFileToPathFromZip('/', $value);
                }

                if ($value === $this->dumpFileName) {
                    $this->deployDataBase(false);
                }
            }

            $this->zip->close();
            $this->setConfigIniValues();
            $this->removeSystemJsonFiles();
            $this->removeCopiedRemoteArchive();
        } else {
            throw new \SystemException('', 'backend.errors.could_not_open_zip', 'json');
        }
    }

    private function setDontTouchFiles($files)
    {
        $this->dontTouchFiles = $files;
    }

    private function setRemoveFiles($files)
    {
        $this->removeFiles = $files;
    }

    private function couldExtractFile($filePath)
    {
        foreach ($this->dontTouchFiles as $value) {
            if (stripos($filePath, $value) > -1) {
                if (!file_exists($filePath)) {
                    return true;
                }

                return false;
            } else {
                return true;
            }
        }
    }

    private function setSystemJsonValuesInZip()
    {
        $systemJson = $this->getSystemJsonAsPhp();
        $this->setDontTouchFiles($systemJson->dontTouchFiles);
        $this->setRemoveFiles($systemJson->removeFiles);

        $this->setConfigIni($systemJson->configIni);
    }

    private function setConfigIni($values)
    {
        $this->configIni = $values;
    }

    private function setConfigIniValues()
    {
        $mainConfig = \MainConfiguration::getInstance();
        foreach ($this->configIni as $sectionName => $sectionValues) {
            foreach ($sectionValues as $sectionKey => $sectionValue) {
                $mainConfig->set($sectionName, $sectionKey, $sectionValue);
            }
        }
    }

    private function getSystemJsonAsPhp()
    {
        $systemJson = $this->zip->getFromName('system.json');
        return json_decode($systemJson);
    }

    private function deployBackUpsByPath($path, $textId)
    {
        foreach ($this->zipFiles as $value) {
            $explodedValue = explode('/', $this->gerAlignmentPath($value));
            if ($explodedValue[0] === $textId) {
                $this->copyFileToPathFromZip($path, $value);
            } else if ($explodedValue[0] === 'client') {
                if ($explodedValue[1] === $textId) {
                    $this->copyFileToPathFromZip($path, $value);
                } else if ($explodedValue[1] === 'templates' && $textId === 'site_templates') {
                    $this->copyFileToPathFromZip($path, $value);
                } else if ($explodedValue[1] === 'email' && $explodedValue[2] === 'emarket' && $textId === 'email_emarket_templates') {
                    $this->copyFileToPathFromZip($path, $value);
                } else if ($explodedValue[1] === 'email' && $explodedValue[2] === 'users' && $textId === 'email_restore_path_templates') {
                    $this->copyFileToPathFromZip($path, $value);
                }
            }
        }
    }

    private function gerAlignmentPath($value){
        return str_replace('\\', '/', $value);
    }

    private function deployDataBase($dropAllTabels = true)
    {
        $dumpFile = $this->zip->getFromName($this->dumpFileName);

        if(!$dumpFile){
            return false;
        }

        if ($dropAllTabels) {
            \DataBase::dropAllTablesAtDataBase();
        }

        $dumpExplodedString = explode("\n", $dumpFile);
        $templine = '';
        foreach ($dumpExplodedString as $line) {
            $substrLine = substr($line, 0, 2);
            if ($substrLine == '--' || $line == '' || $substrLine == '/*' || !$line) {
                continue;
            }

            $templine .= $line;
            if (substr(trim($line), -1, 1) == ';') {
                \DataBase::justQueryToDataBase($templine);
                $templine = '';
            }
        }
    }

    private function copyFileToPathFromZip($path, $zipPath)
    {
        $realPath = realPath(CURRENT_WORKING_DIR);
        if($path !== '/'){
            $realPath .= $path;
        }
        
        $this->zip->extractTo($this->gerAlignmentPath($realPath), $this->gerAlignmentPath($zipPath));
    }

    private function setBackUpZipFiles()
    {
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $entry = $this->zip->getNameIndex($i);
            $this->zipFiles[] = $entry;
        }
    }

    private function setBackUpZipFilesBySystemJson()
    {
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $entry = $this->zip->getNameIndex($i);
            $this->zipFiles[] = $entry;
        }
    }

    private function printBackUpZipFile($filename)
    {
        $filepath = CURRENT_WORKING_DIR . "/backups/export/";
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($filepath . $filename));
        ob_end_flush();
        @readfile($filepath . $filename);
    }

    private function addAboutProgramFileToBackUp()
    {
        $fileName = 'about_program.json';
        $fullPath = CURRENT_WORKING_DIR . "/libs/root-src/" . $fileName;
        $this->zip->addFile($fullPath, $fileName);
    }

    private function addToBackUpsByPath($path, $textId)
    {
        $rootPath = CURRENT_WORKING_DIR . $path;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                if (!($this->endsWith($filePath, '.') && $this->endsWith($filePath, '..'))) {
                    $delimetr = '\\';
                    $pathToFile = explode($path, $name);
                    $relativePath = str_replace('/', $delimetr, $path . $pathToFile[1]);
                    $relativePath = substr($relativePath, 1);
                    $explodedRelativePath = explode($delimetr, $relativePath);
                    if ($explodedRelativePath[0] !== $textId && ($textId === 'css' || $textId === 'images' || $textId === 'fonts' || $textId === 'images')) {
                        $explodedRelativePath[0] = $textId;
                        $relativePath = join($delimetr, $explodedRelativePath);
                    }

                    $this->zip->addFile($filePath, $relativePath);
                }
            }
        }
    }

    private function removeSystemJsonFiles()
    {
        foreach ($this->removeFiles as $fileName) {
            $path = '/' . $fileName;
            \FilesOperations::deleteDirectoryOrFile($path);
        }
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length === 0 ||
            (substr($haystack, -$length) === $needle);
    }

    private function getDumpInstance()
    {
        $config = \MainConfiguration::getInstance();
        $dump = new IMysqldump\Mysqldump('mysql:host=' . $config->get('connections', 'core.host') . ';dbname=' . $config->get('connections', 'core.dbname'), $config->get('connections', 'core.login'), $config->get('connections', 'core.password'));
        return $dump;
    }

    private function addToBackUpsDataBase()
    {
        $dump = $this->getDumpInstance();
        $dump->start($this->dumpFilePath);
        $this->zip->addFile($this->dumpFullPath, $this->dumpFileName);
    }

    private function copyArchiveFromRemoteServer($remoteFileUrl)
    {
        $copiedArchiveName = 'remote' . mt_rand() . '.zip';
        copy($remoteFileUrl, $copiedArchiveName);
        $this->copiedArchiveName = $copiedArchiveName;
    }

    private function removeCopiedRemoteArchive()
    {
        \FilesOperations::deleteFile($this->copiedArchiveName, true);
    }
}