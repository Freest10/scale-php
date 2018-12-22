<?php

class FilesOperations
{

    public static function deleteFile($filePath, $beginNotFromCurrentDir = false)
    {
        $folder_path = $beginNotFromCurrentDir ? $filePath : CURRENT_WORKING_DIR . $filePath;
        unlink($folder_path);
    }

    public static function deleteDirectory($dirname)
    {
        if (is_dir($dirname))
            $dir_handle = opendir($dirname);
        if (!$dir_handle)
            return false;
        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname . "/" . $file))
                    unlink($dirname . "/" . $file);
                else
                    FilesOperations::deleteDirectory($dirname . '/' . $file);
            }
        }
        closedir($dir_handle);
        rmdir($dirname);
        return true;
    }

    public static function deleteDirectoryOrFile($path)
    {
        if (is_dir($path)) {
            FilesOperations::deleteDirectory($path);
        } else {
            FilesOperations::deleteFile($path);
        }
    }

    public static function issetFile($filePath)
    {
        if ($filePath) {
            $folder_path = CURRENT_WORKING_DIR . $filePath;
            return file_exists($folder_path);
        } else {
            return false;
        }
    }

    public static function getXmlAsPhp($filePath)
    {
        if ($filePath) {
            return simplexml_load_file(CURRENT_WORKING_DIR . $filePath);
        } else {
            return false;
        }
    }

    public static function getFileFullPathFromRemoteServer($filePath)
    {
        $remoteServer = \ClassesOperations::autoLoadClass('\Controller\RemoteServer', '/controllers/RemoteServer.php');
        if ($filePath) {
            return $remoteServer->getRemoteServerPath() . $filePath;
        } else {
            return false;
        }
    }

    public function readJsonFileAsPhp($file_name, $beginNotFromCurrentDir = false)
    {
        $fileContent = $this->readFile($file_name, $beginNotFromCurrentDir);
        return json_decode($fileContent);
    }

    public function writePhpAsJsonFile($file_name, $value)
    {
        $fileContent = json_encode($value);
        $this->writeToFile($file_name, $fileContent, true);
    }

    public function deleteAllFilesAtFolder($path)
    {
        $folder_path = CURRENT_WORKING_DIR . $path;
        if (file_exists($folder_path)) {
            foreach (glob($folder_path . '/*') as $file) {
                unlink($file);
            }
        }
    }

    public function createFileWithText($file_name, $text)
    {
        $path = CURRENT_WORKING_DIR . $file_name;
        $fp = fopen($path, "w");
        fwrite($fp, $text);
        fclose($fp);
    }

    public function readFile($file_name, $beginNotFromCurrentDir = false)
    {
        if ($beginNotFromCurrentDir) {
            $path = $file_name;
        } else {
            $path = CURRENT_WORKING_DIR . $file_name;
        }

        $contents = file_get_contents($path);
        return $contents;
    }

    public function writeToFile($filePath, $text, $beginWithCurrentDir = false)
    {
        if ($beginWithCurrentDir) {
            $path = CURRENT_WORKING_DIR . $filePath;
        } else {
            $path = $filePath;
        }

        if ($filePath) {
            file_put_contents($path, $text);
        }
    }

    public function createFolders($filePath)
    {
        $filePath = CURRENT_WORKING_DIR . $filePath;
        mkdir($filePath);
    }

    public function clearFolder($filePath)
    {
        $filePath = CURRENT_WORKING_DIR . $filePath;
        if (file_exists($filePath)) {
            foreach (glob($filePath . "/*") as $file) {
                unlink($file);
            }
        }
    }
}