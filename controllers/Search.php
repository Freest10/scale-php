<?php

namespace Controller;
class Search
{

    private $indexedFields;
    private $pages;
    private $structDataExpl;
    private $explodedBigStrings;
    private $config_data;
    private $files;
    private $searchOptions;
    private $pathToFolderSearch;
    private $pageController;
    private $searchShowValues = NULL;

    public function index_search()
    {
        $this->setConfigData();
        $this->deleteAllCachedFiles();
        $this->setPropsIdWhichIndexed();
        $this->setActivePages();
        $this->pageController = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
        $this->structDataExpl = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $this->structDataExpl->structTypeId = 1;
        $this->setValueForPages();
        $this->createJsonFiles();
        \Response::goodResponse();
    }

    private function createJsonFiles()
    {
        $result = [];
        $count = 0;
        $file_num = 1;
        //разбиваем данные по файлам
        foreach ($this->pages as $key => $value) {
            if ((int)$this->config_data["system"]["search_explode_pages"] > $count) {
                $count++;
            } else {
                $this->createJsonFile($file_num, $result);
                $count = 0;
                $result = [];
                $file_num++;
            }
            $result[$key] = $value;
        }

        //записываем остатки данных в файл
        if (count($result) > 0) {
            $this->createJsonFile($file_num, $result);
        }
    }

    private function createJsonFile($file_num, $data)
    {
        $path = "/cache/data/search/" . $file_num . ".json";
        $jsonData = \JsonOperations::getJsonFromPhp($data, JSON_UNESCAPED_UNICODE);
        $this->files->createFileWithText($path, $jsonData);
    }

    private function setConfigData()
    {
        $mainConfig = \MainConfiguration::getInstance();
        $this->config_data = $mainConfig->getParsedIni();
    }

    private function deleteAllCachedFiles()
    {
        $this->createFileOpertaions();
        $this->files->deleteAllFilesAtFolder('/cache/data/search');
    }

    private function createFileOpertaions()
    {
        $this->files = \ClassesOperations::autoLoadClass('\FilesOperations', '/libs/systems_classes/files.php');
    }

    private function setPropsIdWhichIndexed()
    {
        $this->indexedFields = [];
        $fieldsController = \ClassesOperations::autoLoadClass('\Controller\Fields', '/controllers/Fields.php');
        $reqToDb = \DataBase::justQueryToDataBase("SELECT id  FROM dop_properties_fields  WHERE indexed = 1");
        while ($responseFromDb = \DataBase::responseFromDataBase($reqToDb)) {
            $this->indexedFields[$responseFromDb['id']] = $fieldsController->getFieldTypeByFieldId($responseFromDb['id']);
        }
    }

    private function setActivePages()
    {
        $this->pages = [];
        $reqToDb = \DataBase::justQueryToDataBase("SELECT id  FROM page_id_active  WHERE active = 1");
        while ($responseFromDb = \DataBase::responseFromDataBase($reqToDb)) {
            $this->pages[$responseFromDb['id']] = [];
        }
    }

    private function setValueForPages()
    {
        foreach ($this->pages as $key => $value) {
            $this->pages[$key]["values"] = $this->getIndexedValuesForPage($key);
            $this->pages[$key]["typeId"] = $this->pageController->getTypePageById($key);
            $this->pages[$key]["pageName"] = $this->pageController->getPageName($key);
            $this->pages[$key]["url"] = $this->pageController->getFullPathPage($key);
        }
    }

    private function getIndexedValuesForPage($id)
    {
        $respValue = [];
        foreach ($this->indexedFields as $key => $value) {
            $fieldValue = trim($this->structDataExpl->getFieldValue($key, $value, $id));
            // Множественный список
            if ($value == 5) {
                if (count($fieldValue) > 0) {
                    foreach ($fieldValue as $value) {
                        $respValue[] = $this->structDataExpl->getFieldTextValueById($value);
                    }
                }
                // Составное
            } else if ($value == 12) {
                if (count($fieldValue) > 0) {
                    foreach ($fieldValue as $value) {
                        $respValue[] = $value["name"];
                    }
                }

                // Простой текст
            } else if ($value == 6 || $value == 7) {
                $this->explodeString(strip_tags($fieldValue));
                $respValue = array_merge($respValue, $this->explodedBigStrings);
            } else {
                //Список
                if ($value == 4) {
                    $fieldValue = $this->structDataExpl->getFieldTextValueById($fieldValue);
                }

                //Строка
                if ($value == 1) {
                    $fieldValue = strip_tags($fieldValue);
                }

                if ($fieldValue != '') {
                    $respValue[] = $fieldValue;
                }
            }

        }

        //Title and description page to search
        $titleAndDescriptionReq = $this->getTitleAndDescriptionsPage($id);
        $fieldValueTitle = strip_tags($titleAndDescriptionReq["title"]);
        $fieldValueDescription = strip_tags($titleAndDescriptionReq["description"]);
        if ($fieldValueTitle != '') {
            $respValue[] = $fieldValueTitle;
        }

        if ($fieldValueDescription != '') {
            $respValue[] = $fieldValueDescription;
        }

        return $respValue;
    }

    private function getTitleAndDescriptionsPage($id)
    {
        return $this->pageController->getTitleAndDescriptionPageById($id);
    }

    private function explodeString($string)
    {
        if ($string != '') {
            $this->explodedBigStrings = [];
            preg_match_all('/[(.)|(!)|(?)]/', $string, $matches, PREG_OFFSET_CAPTURE);
            $this->substrBigStrings($string, $matches);
        }
    }

    private function substrBigStrings($string, $matches)
    {
        $prevNum = 0;
        foreach ($matches[0] as $value) {
            $num = ++$value[1];
            $lengthExplode = $num - $prevNum;
            $rede = substr($string, $prevNum, $lengthExplode);
            $lengthString = strlen($rede);
            if ($lengthString > $this->config_data["system"]["max_search_string_length"]) {
                $this->explodeBigRede($rede, $lengthString);
            } else {
                $this->explodedBigStrings[] = trim($rede);
            }
            $prevNum = $num;
        }
    }

    private function explodeBigRede($rede, $length)
    {
        $residue = $length % $this->config_data["system"]["max_search_string_length"];
        $countRedes = ($length - $residue) / $this->config_data["system"]["max_search_string_length"];
        $numsOfExplodes = [];
        preg_match_all('/ /', $rede, $matches, PREG_OFFSET_CAPTURE);
        $prev = 0;
        $lengthOfExplode = $this->config_data["system"]["max_search_string_length"];
        foreach ($matches[0] as $value) {
            if ($value[1] > $lengthOfExplode) {
                $numsOfExplodes[] = $prev;
                $lengthOfExplode += $this->config_data["system"]["max_search_string_length"];
            }
            $prev = $value[1];
        }

        $numsOfExplodes[] = $length;
        $prevNum = 0;
        foreach ($numsOfExplodes as $value) {
            $lengthExplode = $value - $prevNum;
            $explodeRede = substr($rede, $prevNum, $lengthExplode);
            $prevNum = $value;
            $this->explodedBigStrings[] = trim($explodeRede);
        }
    }

    public function searchPages($options)
    {
        if ($options["searchString"]) {
            $options["searchString"] = trim($options["searchString"]);
            $this->createFileOpertaions();
            $this->setSearchOptions($options);
            $this->setPathToFolderSearch("/cache/data/search");
            $filesInSearchFolder = scandir($this->pathToFolderSearch);
            $this->parseSearchFiles($filesInSearchFolder);
            $this->explodeOnPageSearchPages();
        }

        return $this->searchShowValues;
    }

    private function explodeOnPageSearchPages()
    {
        $pageNum = $this->searchOptions["page"];
        if ($this->searchOptions["page"] < 0) {
            $pageNum = 1;
        }
        $pageNum--;
        if ($this->searchOptions["limit"] > 0) {
            $beginNum = ($pageNum * $this->searchOptions["limit"]);
            $this->searchShowValues["value"] = array_slice($this->searchShowValues["value"], $beginNum, $this->searchOptions["limit"]);
        }
        $this->searchShowValues["page"] = $this->searchOptions["page"];
        $this->searchShowValues["limit"] = $this->searchOptions["limit"];
    }

    private function setPathToFolderSearch($pathToFolderSearch)
    {
        $this->pathToFolderSearch = CURRENT_WORKING_DIR . $pathToFolderSearch;
    }

    private function setSearchOptions($searchOptions)
    {
        $this->searchOptions = $searchOptions;
    }

    private function parseSearchFiles($files)
    {
        foreach ($files as $value) {
            if (stripos($value, ".json")) {
                $this->doSearchInFiles($value);
            }
        }
    }

    private function doSearchInFiles($filePath)
    {
        $path = $this->pathToFolderSearch . "/" . $filePath;
        $fileText = $this->files->readFile($path, true);
        if ($fileText) {
            $phpFileText = json_decode($fileText, true);
            $this->findStringAtFileText($phpFileText);
        }
    }

    private function findStringAtFileText($phpFileText)
    {
        $this->searchShowValues = [];
        $this->searchShowValues["value"] = [];
        $total = 0;
        foreach ($phpFileText as $id => $page) {
            if (count($this->searchOptions["typeIds"]) > 0) {
                if (!(in_array($page["typeId"], $this->searchOptions["typeIds"]))) {
                    continue;
                }
            }
            $resultSearchString = $this->searchStringAtPageData($page["values"]);
            if ($resultSearchString) {
                $data = [];
                $data["id"] = $id;
                $data["value"] = $resultSearchString;
                $data["typeId"] = $page["typeId"];
                $data["pageName"] = $page["pageName"];
                $data["url"] = $page["url"];
                $total++;
                array_push($this->searchShowValues["value"], $data);
            }
        }
        $this->searchShowValues["total"] = $total;
    }

    private function searchStringAtPageData($pageValues)
    {
        $stringValue = "";
        foreach ($pageValues as $pageValue) {
            if (mb_stristr($pageValue, $this->searchOptions["searchString"])) {
                $valueResultChange = "<b>" . $this->searchOptions["searchString"] . "</b>";
                $stringValue .= str_ireplace($this->searchOptions["searchString"], $valueResultChange, $pageValue);
                $stringValue .= " ";
            }
        }
        return trim($stringValue);
    }

}