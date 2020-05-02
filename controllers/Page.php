<?php

namespace Controller;
class Page
{
    public static function getDopPropertiesPageById($id)
    {
        return \DataBase::queryToDataBase("SELECT * FROM dop_properties_page WHERE id = " . $id);
    }

    public static function getParentIdById($id)
    {
        return \DataBase::queryToDataBase("SELECT parent_id FROM page_parent_id WHERE id = " . $id)["parent_id"];
    }

    public static function getTypePageById($id)
    {
        return \DataBase::queryToDataBase("SELECT template_id FROM page_id_to_template_id WHERE page_id = " . $id)["template_id"];
    }

    public static function getActive($id)
    {
        $activeStatus = \DataBase::queryToDataBase("SELECT active FROM page_id_active WHERE id = " . $id)['active'];
        return $activeStatus;
    }

    public static function getPageName($id)
    {
        $pageNameFromDbQuery = \DataBase::queryToDataBase("SELECT name FROM page_id_name WHERE id = " . $id);
        return $pageNameFromDbQuery['name'];
    }

    public static function getPageIdByFullPath($full_path, $onlyActive = false)
    {
        $sqlActivePage = "";
        if ($onlyActive) {
            $sqlActivePage = "INNER JOIN page_id_active as piactv ON (pgiuri.page_id = piactv.id AND piactv.active = 1)";
        }

        $idFromDbQuery = \DataBase::queryToDataBase("SELECT pgiuri.page_id as id FROM page_id_uri as pgiuri " . $sqlActivePage . " WHERE full_path = '" . $full_path . "'");
        return $idFromDbQuery['id'];
    }

    public static function isMainPageId($id)
    {
        $mainPageId = \DataBase::queryToDataBase("SELECT id FROM main_page WHERE id= $id")['id'];
        if ($mainPageId > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    private $id;
    private $page;
    public $pageData;
    public $pageTemplateId;
    private $struct_data;
    private $parentsPages = [];
    private $pages = [];
    private $sortValPage;
    private $langs;
    private $subDomain;
	private $fields;

    function __construct()
    {
        $this->langs = \Langs::getInstance();
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
		$this->fields = \ClassesOperations::autoLoadClass('\Controller\Fields', '/controllers/Fields.php');
    }

    public function getPageById($id)
    {
        $this->page = [];
        $this->id = $id;
        $this->page['name'] = $this->getPageName($this->id);
        $this->pageTemplateId = $this->getPageTemplateId($this->id);
        $this->getGeneralPageGroups();
        $this->getGeneralPageFields();
        $this->struct_data->structId = $id;
        $this->struct_data->structTemplateId = $this->pageTemplateId;
        $this->struct_data->structTypeId = 1;
        $this->struct_data->generateStructData();
        $this->mergeWithStructData();
        \JsonOperations::printJsonFromPhp($this->page);
    }

    public function getParentPagesById($id)
    {
        $parentPagesReqToDb = \DataBase::justQueryToDataBase("SELECT parent_id  FROM page_parent_id  WHERE id = " . $id);
        while ($responseFromDb = \DataBase::responseFromDataBase($parentPagesReqToDb)) {
            if ($responseFromDb['parent_id'] != 0) {
                $data = [];
                $data['id'] = $responseFromDb['parent_id'];
                $data['text'] = $this->getPageName($responseFromDb['parent_id']);
                $data['type_id'] = $this->getPageTemplateId($responseFromDb['parent_id']);
                $this->parentsPages[] = $data;
                $this->getParentPagesById($responseFromDb['parent_id']);
            }
        }

        return $this->parentsPages;
    }

    public function getFirstPageIdByType($templateId)
    {
        return \DataBase::queryToDataBase("SELECT page_id FROM page_id_to_template_id WHERE template_id = " . $templateId)["page_id"];
    }


    public function setSubDomain($value)
    {
        $this->subDomain = $value;
    }

    public function getPagesReqForSubDomain($subDomainId, $begin, $limit)
    {
        return \DataBase::justQueryToDataBase("
                SELECT pin.last_mod as 'last_mod', piuri.full_path as 'path'
                FROM page_id_name as pin 
                INNER JOIN page_id_uri piuri ON pin.id = piuri.page_id
                INNER JOIN page_parent_id ppid ON (pin.id= ppid.id AND ppid.sub_domain= $subDomainId)
                INNER JOIN page_id_active pigactive ON  pin.id=pigactive.id AND active=1 AND no_indexed=0
                LIMIT $begin, $limit
                ");
    }

    public function getCountPagesForSubDomain($subDomainId)
    {
        return \DataBase::queryToDataBase("
                SELECT COUNT(pin.id) as 'total'
                FROM page_id_name as pin 
                INNER JOIN page_id_uri piuri ON pin.id = piuri.page_id
                INNER JOIN page_parent_id ppid ON pin.id = ppid.id AND sub_domain = $subDomainId
                INNER JOIN page_id_active pigactive ON  pin.id=pigactive.id AND active=1 AND no_indexed=0
                ")["total"];
    }

    public function setPageTemplateId($pageId, $templateId)
    {
        $templatesType = \ClassesOperations::autoLoadClass('\Controller\TemplatesType', '/controllers/TemplatesType.php');
        if ($templatesType::isSetTemplate($templateId)) {
            \DataBase::justQueryToDataBase("UPDATE page_id_to_template_id SET template_id = '" . $templateId . "' WHERE page_id = " . $pageId);
            return true;
        }

        return false;
    }

    public function getTitleAndDescriptionPageById($id)
    {
        return \DataBase::queryToDataBase("SELECT title, description FROM dop_properties_page WHERE id = " . $id);
    }

    public function getPathArr()
    {
        include_once CURRENT_WORKING_DIR . '/libs/root-src/requests.php';
        $request = new \Requests();
        $reqArr = $request->getUriMassive();
        array_shift($reqArr);
        return $reqArr;
    }
	
	public function getSqlStringForOrder($sortTable, $orderBy, $sortBySortNum, $sortNullLast)
    {
        if (!$orderBy) {
            $orderBy = 'ASC';
        }

        if ($sortTable) {
			if(is_array($sortTable)) {
				foreach ($sortTable as $key => $value) {
					$orderString = " ORDER BY tblbytype".$key.".value ". $orderBy;
				}
			} else {
				if(sortNullLast) {
					$orderString = " ORDER BY tblbytype.value IS NULL, tblbytype.value " . $orderBy;
				}else{
					$orderString = " ORDER BY tblbytype.value " . $orderBy;
				}
			}
        } else if ($sortBySortNum) {
            $orderString = " ORDER BY sortNum " . $orderBy;
        } else {
            $orderString = " ORDER BY name " . $orderBy;
        }

        return $orderString;
    }
	
	public function getTotalChildPages($options)
    {
		$id = $options["childOf"];
		$findNoActives = $options["noActive"];
		$filters = $options['filters'];
		 $depth = $options["depth"];
		
		if ((int)$depth === 0) return false;

        $sqlJoins = "";
        if ($typeIds) {
            $sqlJoins .= $this->sqlStringByTypeIds($typeIds, 1);
        }

        if ($filters) {
            $sqlJoins .= $this->sqlStringByFilters($filters, 1);
        }
		
		$sqlString = "SELECT count(*) as total FROM page_parent_id AS pprnt1 " . $sqlJoins . " WHERE pprnt1.parent_id = " . $id;
		

        $sqlChildrenPages = $this->getSqlStringForChildrenPages($depth, $id);
		
		$sqlString .= $sqlChildrenPages;
		$totalPages = \DataBase::queryToDataBase($sqlString);
		
		return (int)$totalPages["total"];		
	}

    public function getChildPages($options)
    {
        $id = $options["childOf"];
        $props = $options["props"];
        $groups = $options["groups"];
        $depth = $options["depth"];
        $page = $options["begin"];
        $limit = $options["limit"];
        $findNoActives = $options["noActive"];
		$findNoActiveOnly = $options["noActiveOnly"];
        $typeIds = $options["typeIds"];
        $sortField = $options['sortByField'];
        $sort = $options['sort'];
        $filters = $options['filters'];
		$sortNullLast = $options['sortNullLast'];
		$sortBySortNum = $options['sortBySortNum'];
        if ((int)$depth === 0) return false;

        $sqlJoins = "";
        if ($typeIds) {
            $sqlJoins .= $this->sqlStringByTypeIds($typeIds, 1);
        }

        if ($filters) {
            $sqlJoins .= $this->sqlStringByFilters($filters, 1);
        }

        $sortTable = is_array($sortField) ? $this->getTableNamesOfSortedField($sortField) : $this->getTableNameOfSortedField($sortField);
        $orderBy = $this->getOrderValue($sort);
        $joinOrderString = is_array($sortField) ? $this->getSqlStringToJoinsOrder($sortTable, $sortField) : $this->getSqlStringToJoinOrder($sortTable, $sortField);

        $orderString = $this->getSqlStringForOrder($sortTable, $orderBy, $sortBySortNum, $sortNullLast);
        $sqlJoinPageInfo = $this->getSqlPageInfoJoin($findNoActives, $findNoActiveOnly);

        $sqlString = "SELECT SQL_CALC_FOUND_ROWS distinct pprnt1.id as id, pprnt1.parent_id as parent_id, pgname.name as name, pgtmplt.template_id as template_id, pguri.full_path as full_path, dpp.h1 as h1, dpp.title as title, dpp.description as description FROM page_parent_id AS pprnt1 " . $joinOrderString . $sqlJoinPageInfo . $sqlJoins . " WHERE pprnt1.parent_id = " . $id;

        $sqlChildrenPages = $this->getSqlStringForChildrenPages($depth, $id);
        $sqlString .= $sqlChildrenPages;
        $sqlString .= $orderString;
        $sqlLimit = $this->getSqlStringLimit($page, $limit);
        $sqlString .= $sqlLimit;
        $sqlStringSecond = "SELECT FOUND_ROWS()";
	
        $childrenPagesReqToDb = \DataBase::justQueryToDataBase($sqlString);
        $result = [];
        $pages = [];

        $totalPages = \DataBase::queryToDataBase($sqlStringSecond)["FOUND_ROWS()"];
        $pathArr = $this->getPathArr();
        $activePath = "/";
        if (count($pathArr) > 0) {
            if ($pathArr[0] !== "") {
                $activePath .= $this->concatPath($pathArr);
            }
        }

        while ($responseFromDb = \DataBase::responseFromDataBase($childrenPagesReqToDb)) {
            $data = [];
            $dataId = $responseFromDb["id"];
            $data["text"] = $responseFromDb["name"];
            $data["templateId"] = $responseFromDb["template_id"];
            $data["parentId"] = $responseFromDb["parent_id"];
            $data["url"] = $responseFromDb["full_path"];
            $data["active"] = false;
            $data["childrenActive"] = false;
            $data["h1"] = $responseFromDb["h1"];
            $data["title"] = $responseFromDb["title"];
            $data["description"] = $responseFromDb["description"];
            if ($activePath == $data["url"]) {
                $data["active"] = true;
            } else {
                $data["childrenActive"] = $this->isHaveChildrenActivePages($pathArr, $data["url"]);
            }

            if (count($props) > 0) {
                $this->struct_data->structId = $dataId;
                $this->struct_data->structTypeId = 1;
                $this->struct_data->structTemplateId = $data["templateId"];
                $data["props"] = $this->struct_data->getFieldsByPageIdForClient($props);
            }

            if (count($groups) > 0) {
                $this->struct_data->structId = $dataId;
                $this->struct_data->structTypeId = 1;
                $this->struct_data->structTemplateId = $data["templateId"];
                $data["groups"] = $this->struct_data->getGroupsByPageIdForClient($groups);
            }

            $pages[$dataId] = $data;
        }

        $result["pages"] = $pages;
        $result["total"] = count($pages) > 0 ? $totalPages : 0;
        return $result;
    }

    public function getBreadCrumbs()
    {
        $pathArr = $this->getPathArr();
        $reqPages = [];
        $reqPages["pageIds"] = [];
        $mainPageId = $this->getMainPageId();
        array_push($reqPages["pageIds"], $mainPageId);

        if (count($pathArr) > 0) {
            foreach ($pathArr as $key => $value) {
                $pageId = $this->getPageIdByUri($value);
                if ($pageId > 0 && $pageId != NULL) {
                    array_push($reqPages["pageIds"], $pageId);
                }
            }
        }

        $result = [];
        $result["pages"] = $this->getPagesByIdForClient($reqPages);
        $result["total"] = count($reqPages["pageIds"]);

        return $result;
    }

    public function getPageIdByUri($uri)
    {
        $idFromDbQuery = \DataBase::queryToDataBase("SELECT page_id as id FROM page_id_uri WHERE uri = '" . $uri . "'");
        return $idFromDbQuery['id'];
    }

    public function getFullPathPage($id)
    {
        $uriReqDb = \DataBase::queryToDataBase("SELECT full_path FROM page_id_uri WHERE page_id = " . $id)['full_path'];
        return $uriReqDb;
    }

    public function getIndexedValById($id)
    {
        $indexedStatus = \DataBase::queryToDataBase("SELECT no_indexed FROM page_id_active WHERE id = " . $id)['no_indexed'];
        return $indexedStatus;
    }

    public function getPageTemplateId($id)
    {
        $pageTemplateFromDbQuery = \DataBase::queryToDataBase("SELECT template_id FROM page_id_to_template_id WHERE page_id=" . $id);
        return $pageTemplateFromDbQuery['template_id'];
    }

    public function setPageById($id)
    {
        $this->id = $id;
        $this->setPageName($this->id, $this->pageData['name']);
        $this->setPageTemplate();
        $this->struct_data->structId = $this->id;
        $this->struct_data->structTypeId = 1;
        $this->struct_data->structTemplateId = $this->getTemplateIdFromPageData();
        $this->struct_data->setFieldsData($this->pageData);
        \Response::goodResponse();
    }

    public function setPageName($pageId, $pageName)
    {
        \DataBase::justQueryToDataBase("UPDATE page_id_name SET name = '" . $pageName . "', last_mod='" . $this->getNowDateISO() . "' WHERE id = " . $pageId);
    }

    public function createPage($parent_id, $pageName = null, $templateId = null)
    {
        $parent_id = (int)$parent_id;
        $this->createPageName($pageName);
        $this->id = $this->getIdLastPage();
        $this->sortValPage = $this->getSortValueCreatedPageByParentId($parent_id);
        $this->createPageByParentId($parent_id);
        $this->pageTemplateId = $templateId ? $templateId : $this->getPageTemplateId($parent_id);
        $this->createTypePage($this->pageTemplateId);
        $activeParent = $this->getActive($parent_id);
        $this->createActivePage($activeParent);
        $this->createUriPage($this->id);
        $this->struct_data->structId = $this->id;
        $this->struct_data->setterUriAndChildrenPath($this->id);
        $this->struct_data->createDopProperties($this->id);
        $this->setCountSortNumToAllApges();
        return $this->id;
    }

    public function getIdLastPage()
    {
        return \DataBase::queryToDataBase("SELECT MAX(id) FROM page_id_name")['MAX(id)'];
    }

    public function createPageName($pageName = null)
    {
        $langWord = $pageName ? $pageName : $this->langs->getMessage("backend.new_page");
        \DataBase::justQueryToDataBase("INSERT page_id_name SET name = '" . $langWord . "', last_mod='" . $this->getNowDateISO() . "'");
    }

    public function createTypePageById($templateId, $pageId)
    {
        $this->id = $pageId;
        $this->createTypePage($templateId);
    }
	
	public function getOrderValue($value)
    {
        if ($value === "DESC" || $value === "ASC") {
            return $value;
        }

        return NULL;
    }
	
	public function getSqlStringToJoinsOrder($sortTable, $fieldName)
    {
        $joinOrderString = "";
        foreach ($sortTable as $key => $value) {
			$joinOrderString .= $this->getSqlStringToJoinOrder($value, $fieldName[$key], $key);
			$joinOrderString .= " "; 
		}

        return $joinOrderString;
    }
	
	public function getSqlStringToJoinOrder($sortTable, $fieldName, $index = '')
    {
        $joinOrderString = "";
        if ($sortTable) {
			$fieldId = $this->fields->getIdFieldByTextId($fieldName);
            $joinOrderString = "LEFT JOIN " . $sortTable . " AS tblbytype".$index." ON pprnt1.id = tblbytype".$index.".page_id AND tblbytype".$index.".field_id = ".$fieldId;
        }
        return $joinOrderString;
    }
	
	public function getMinMaxValue($options) {
		$id = $options["childOf"];
		$findNoActives = $options["noActive"];
		$filters = $options['filters'];
		$depth = $options["depth"];
		$minFieldsValues = $options["minFieldsValue"];
		$maxFieldsValue = $options["maxFieldsValue"];
		
		if ((int)$depth === 0) return false;

        $sqlJoins = "";
        if ($typeIds) {
            $sqlJoins .= $this->sqlStringByTypeIds($typeIds, 1);
        }

        if ($filters) {
            $sqlJoins .= $this->sqlStringByFilters($filters, 1);
        }
		
		$tableTypeName = 'ftable';
		$minPostFix = 'MIN';
		$maxPostFix = 'MAX';
		$selectMin = $this->getSelectStringMinMax($minFieldsValues, $tableTypeName, $minPostFix);
		$selectMax = $this->getSelectStringMinMax($maxFieldsValue, $tableTypeName, $maxPostFix);
		
		$sqlJoinMin = $this->getSqlStringByMinMaxFilters($minFieldsValues, $tableTypeName, $minPostFix);
		$sqlJoinMax = $this->getSqlStringByMinMaxFilters($maxFieldsValue, $tableTypeName, $maxPostFix);
		
		if(strlen($selectMin) > 0 && strlen($selectMax) > 0){
			$selectMin .= ', ';
		}
		
		$sqlString = "SELECT ". $selectMin . $selectMax ." FROM page_parent_id AS pprnt1 " . $sqlJoins . " " .$sqlJoinMin. " " .$sqlJoinMax.  " WHERE pprnt1.parent_id = " . $id;
		

        $sqlChildrenPages = $this->getSqlStringForChildrenPages($depth, $id);
		
		$sqlString .= $sqlChildrenPages;
		
		$resultQuery = \DataBase::queryToDataBase($sqlString);
		$result = [];
		
		if (count($minFieldsValues) > 0) {
			$result[$minPostFix] = [];
            foreach ($minFieldsValues as $key => $value) {
                $result[$minPostFix][$value] = $resultQuery[$minPostFix.$key];
            }
        }
		
		if (count($maxFieldsValue) > 0) {
			$result[$maxFieldsValue] = [];
            foreach ($maxFieldsValue as $key => $value) {
                $result[$maxPostFix][$value] = (int)$resultQuery[$maxPostFix.$key];
            }
        }
		
		return $result;
	}

    public function getPagesByIdForClient($options)
    {
        $ids = $options["pageIds"];
        $props = $options["props"];
        $groups = $options["groups"];
        $noActive = $options["noActive"];
        $idsToSql = "";
        if (count($ids) > 0) {
            foreach ($ids as $key => $value) {
                if ($key != 0) {
                    $idsToSql .= " OR";
                }

                $idsToSql .= " pgname.id = " . $value;
            }
        }

        $innerJoinToActivePage = (!$noActive) ? "INNER JOIN page_id_active piactive ON (pgname.id = piactive.id AND piactive.active = 1)" : "";
        $childrenPagesReqToDb = \DataBase::justQueryToDataBase("
			SELECT pgname.id as id, pguri.full_path as full_path, pgname.name as name, pgtmplt.template_id as template_id, pgprnt.parent_id as parent_id, dpp.h1 as h1, dpp.title as title, dpp.description as description
			FROM page_id_name  pgname
			INNER JOIN page_parent_id  pgprnt ON pgname.id = pgprnt.id 
			INNER JOIN page_id_uri pguri ON pgname.id = pguri.page_id 
			INNER JOIN page_id_to_template_id pgtmplt ON pgname.id = pgtmplt.page_id
			INNER JOIN dop_properties_page dpp ON pgname.id = dpp.id
			" . $innerJoinToActivePage . "
			WHERE " . $idsToSql
        );

        $pathArr = $this->getPathArr();
        $activePath = "/";
        if (count($pathArr) > 0) {
            if ($pathArr[0] !== "") {
                $activePath .= $this->concatPath($pathArr);
            }
        }

        while ($responseFromDb = \DataBase::responseFromDataBase($childrenPagesReqToDb)) {
            $data = [];
            $dataId = $responseFromDb["id"];
            $data["text"] = $responseFromDb["name"];
            $data["templateId"] = $responseFromDb["template_id"];
            $data["parentId"] = $responseFromDb["parent_id"];
            $data["url"] = $responseFromDb["full_path"];
            $data["h1"] = $responseFromDb["h1"];
            $data["title"] = $responseFromDb["title"];
            $data["description"] = $responseFromDb["description"];
            $data["active"] = false;
            $data["childrenActive"] = false;
            if ($activePath == $data["url"]) {
                $data["active"] = true;
            } else {
                $data["childrenActive"] = $this->isHaveChildrenActivePages($pathArr, $data["url"]);
            }

            if (count($props) > 0) {
                $this->struct_data->structId = $dataId;
                $this->struct_data->structTypeId = 1;
                $this->struct_data->structTemplateId = $data["templateId"];
                $data["props"] = $this->struct_data->getFieldsByPageIdForClient($props);
            }

            if (count($groups) > 0) {
                $this->struct_data->structId = $dataId;
                $this->struct_data->structTypeId = 1;
                $this->struct_data->structTemplateId = $data["templateId"];
                $data["groups"] = $this->struct_data->getGroupsByPageIdForClient($groups);
            }

            $this->pages[$dataId] = $data;
        }

        return $this->pages;
    }
	
	private function getSelectStringMinMax($fieldNames, $tableAbbreviation, $postFix) {
		$selectString = '';
		
		if(isset($fieldNames) && count($fieldNames) < 0) {
			return $selectString;
		}
		
		foreach ($fieldNames as $key => $value) {
			if ($key != 0) {
				$selectString .= ",";
			}

			$selectString .= $postFix. "(". $tableAbbreviation . $postFix . $key .".field_value) as ". $postFix. $key;
		}
		
		return $selectString;
	}

    private function mergeWithStructData()
    {
        $this->page['groups'] = array_merge($this->page['groups'], $this->struct_data->structData['groups']);
        if (is_array($this->struct_data->structData['fields'])) $this->page['fields'] = array_merge($this->page['fields'], $this->struct_data->structData['fields']);
    }

    private function getActivePropPage()
    {
        if (!is_array($this->page["fields"])) $this->page["fields"] = [];
        $fieldNum = count($this->page["fields"]);
        $this->page["fields"][$fieldNum]["id"] = -3;
        $this->page["fields"][$fieldNum]["parentId"] = 0;
        $this->page["fields"][$fieldNum]["typeId"] = 3;
        $this->page["fields"][$fieldNum]["necessarily"] = 0;
        $this->page["fields"][$fieldNum]["name"] = $this->langs->getMessage("backend.fields.active");
        $this->page["fields"][$fieldNum]["value"] = $this->getActive($this->id);
    }

    private function getIndexedPropPage()
    {
        if (!is_array($this->page["fields"])) $this->page["fields"] = [];
        $fieldNum = count($this->page["fields"]);
        $this->page["fields"][$fieldNum]["id"] = -8;
        $this->page["fields"][$fieldNum]["parentId"] = 0;
        $this->page["fields"][$fieldNum]["typeId"] = 3;
        $this->page["fields"][$fieldNum]["necessarily"] = 0;
        $this->page["fields"][$fieldNum]["name"] = $this->langs->getMessage("backend.fields.no_indexed");
        $this->page["fields"][$fieldNum]["value"] = $this->getIndexedValById($this->id);
    }

    private function getMainPropPage()
    {
        if (!is_array($this->page["fields"])) $this->page["fields"] = [];
        $fieldNum = count($this->page["fields"]);
        $this->page["fields"][$fieldNum]["id"] = -7;
        $this->page["fields"][$fieldNum]["parentId"] = 0;
        $this->page["fields"][$fieldNum]["typeId"] = 3;
        $this->page["fields"][$fieldNum]["necessarily"] = 0;
        $this->page["fields"][$fieldNum]["name"] = $this->langs->getMessage("backend.fields.main");
        $this->page["fields"][$fieldNum]["value"] = $this->isMainPageId($this->id);
    }

    private function getMainPageId()
    {
        $sitePaths = new \SitePaths();
        $subDomainId = $sitePaths->getSubDomainIdForRequest();
        $mainPageId = \DataBase::queryToDataBase("SELECT id FROM main_page WHERE sub_domain=$subDomainId")['id'];
        return $mainPageId;
    }

    private function getGeneralPageGroups()
    {
        $this->page['groups'][0] = [];
        $this->page['groups'][0]["name"] = $this->langs->getMessage("backend.fields.share");
        $this->page['groups'][0]["textId"] = "general_fields";
        $this->page['groups'][0]["id"] = 0;
    }

    private function getGeneralPageFields()
    {
        $this->getActivePropPage();
        $this->getIndexedPropPage();
        $this->getMainPropPage();
        $this->getTemplateFieldPage();
        $this->getUriPageForField();
        $this->getDopPropsPage();
    }

    private function getTemplateFieldPage()
    {
        if (!is_array($this->page["fields"])) $this->page["fields"] = [];
        $arrayToPush = [];
        $arrayToPush["id"] = -1;
        $arrayToPush["parentId"] = 0;
        $arrayToPush["typeId"] = 4;
        $arrayToPush["necessarily"] = 1;
        $arrayToPush["name"] = $this->langs->getMessage("backend.fields.type");
        $arrayToPush["value"] = $this->pageTemplateId;
        array_push($this->page["fields"], $arrayToPush);
    }

    private function getUriPageForField()
    {
        if (!is_array($this->page["fields"])) $this->page["fields"] = [];
        $fieldNum = count($this->page["fields"]);
        $this->page["fields"][$fieldNum]["id"] = -2;
        $this->page["fields"][$fieldNum]["parentId"] = 0;
        $this->page["fields"][$fieldNum]["typeId"] = 1;
        $this->page["fields"][$fieldNum]["necessarily"] = 1;
        $this->page["fields"][$fieldNum]["name"] = $this->langs->getMessage("backend.fields.uri");
        $this->page["fields"][$fieldNum]["value"] = $this->struct_data->getUriPage($this->id);
    }

    private function getDopPropsPage()
    {
        $dop_props = \DataBase::queryToDataBase("SELECT * FROM dop_properties_page WHERE id = " . $this->id);
        $this->doPropFieldForPage($dop_props['h1'], -4, "H1");
        $this->doPropFieldForPage($dop_props['title'], -5, "Title");
        $this->doPropFieldForPage($dop_props['description'], -6, "Description");
    }

    private function doPropFieldForPage($value, $idField, $nameField)
    {
        if (!is_array($this->page["fields"])) $this->page["fields"] = [];
        $fieldNum = count($this->page["fields"]);
        $this->page["fields"][$fieldNum]["id"] = $idField;
        $this->page["fields"][$fieldNum]["parentId"] = 0;
        $this->page["fields"][$fieldNum]["typeId"] = 1;
        $this->page["fields"][$fieldNum]["necessarily"] = 0;
        $this->page["fields"][$fieldNum]["name"] = $nameField;
        $this->page["fields"][$fieldNum]["value"] = $value;
    }

    private function getTemplateIdFromPageData()
    {
        return $this->pageData['fields'][2]["value"];
    }

    private function setPageTemplate()
    {
        \DataBase::justQueryToDataBase("UPDATE page_id_to_template_id SET template_id = '" . $this->getTemplateIdFromPageData() . "' WHERE page_id = " . $this->id);
    }

    private function setCountSortNumToAllApges()
    {
        $sort_pages = \ClassesOperations::autoLoadClass('\Controller\SortPages', '/controllers/SortPages.php');
        $sort_pages->setCountSortNumToAllApges();
    }

    private function getSortValueCreatedPageByParentId($parent_id)
    {
        return \DataBase::queryToDataBase("SELECT COUNT(id) as nums FROM page_parent_id WHERE parent_id=" . $parent_id)["nums"];
    }

    private function createTypePage($template_id)
    {
        if (!$template_id) $template_id = 1;
        \DataBase::justQueryToDataBase("INSERT page_id_to_template_id SET template_id=" . $template_id . ", page_id=" . $this->id);
    }

    private function createActivePage($active)
    {
        if (!is_int($active)) {
            $active = 1;
        }
        \DataBase::justQueryToDataBase("INSERT page_id_active SET active=" . $active . ", id=" . $this->id);
    }

    private function createUriPage($uriValue)
    {
        \DataBase::justQueryToDataBase("INSERT page_id_uri SET uri='" . $uriValue . "', page_id=" . $this->id);
    }

    private function getNowDateISO()
    {
        return date("c");
    }

    private function createPageByParentId($parent_id)
    {
        \DataBase::justQueryToDataBase("INSERT `page_parent_id` SET id=" . $this->id . ", parent_id=" . $parent_id . ", sort=" . $this->sortValPage . ", sub_domain=$this->subDomain");
    }

    public function deletePages($ids)
    {
        if (!is_array($ids)) {
            $ids = json_decode($ids);
        }
        foreach ($ids as $id) {
            $this->deleteChildrenPages($id);
            $this->deletePage($id);
        }
        \Response::goodResponse();
    }
	
	public function getTableNamesOfSortedField($sortFields)
    {
	   $result = [];
       if(is_array ($sortFields)) {
			foreach ($sortFields as $sortFieldName) {
				array_push($result, $this->getTableNameOfSortedField($sortFieldName));
			}
		}
		
		return $result;
    }
	
	public function getTableNameOfSortedField($sortField)
    {
        if ($sortField) {
			$sortedTableName = \DataBase::queryToDataBase("SELECT tf.table_name AS table_name FROM field_id_name AS fin
				INNER JOIN field_id_field_type fift ON fift.id = fin.id 
				INNER JOIN type_fields tf ON tf.id = fift.type_id
				WHERE fin.text_id = '$sortField'
				")["table_name"];
        } else {
            $sortedTableName = NULL;
        }
        return $sortedTableName;
    }

    private function deleteChildrenPages($parent_id)
    {
        $parentPagesReqToDb = \DataBase::justQueryToDataBase("SELECT id  FROM page_parent_id  WHERE parent_id = " . $parent_id);
        while ($responseFromDb = \DataBase::responseFromDataBase($parentPagesReqToDb)) {
            $this->deletePage($responseFromDb['id']);
            $this->deleteChildrenPages($responseFromDb['id']);
        }
    }

    private function deletePage($id)
    {
        $this->deletePageName($id);
        $this->deletePageTemplate($id);
        $this->deletePageActive($id);
        $this->deletePageUri($id);
        $this->deletePageParent($id);
        $this->deleteAllFieldsByPage($id, 1);
    }

    public function deleteAllFieldsByPage($page_id, $type)
    {
        $allTablesFeilds = $this->struct_data->getTypeTables();
        foreach ($allTablesFeilds as $value) {
            \DataBase::justQueryToDataBase("DELETE FROM " . $value . " WHERE type=" . $type . " AND page_id =" . $page_id);
        }
    }

    private function deletePageName($id)
    {
        \DataBase::justQueryToDataBase("DELETE FROM page_id_name WHERE id =" . $id);
    }

    private function deletePageTemplate($id)
    {
        \DataBase::justQueryToDataBase("DELETE FROM page_id_to_template_id WHERE page_id =" . $id);
    }

    private function deletePageActive($id)
    {
        \DataBase::justQueryToDataBase("DELETE FROM page_id_active WHERE id =" . $id);
    }

    private function deletePageUri($id)
    {
        \DataBase::justQueryToDataBase("DELETE FROM page_id_uri WHERE page_id =" . $id);
    }

    private function deletePageParent($id)
    {
        \DataBase::justQueryToDataBase("DELETE FROM page_parent_id WHERE id =" . $id);
    }

    private function concatPath($pathArr)
    {
        $resultString = "";
        if (count($pathArr) > 0) {
            $resultString = join('/', $pathArr);
        }
        return $resultString;
    }

    private function isHaveChildrenActivePages($pathArr, $url)
    {
        $resultString = false;
        $url = str_replace("/", "", $url);
        if (count($pathArr) > 0) {
            foreach ($pathArr as $key => $value) {
                if ($value == $url) {
                    $resultString = true;
                    break;
                }
            }

            if (!$resultString) {
                $joinedPath = join("", $pathArr);
                $pos = strpos($joinedPath, $url);
                $resultString = $pos !== false;
            }
        }

        return $resultString;
    }

    private function sqlStringByTypeIds($typeIds, $num)
    {
        $sqlString = "";
        if (!$num) {
            $num = "";
        }

        if (count($typeIds) > 0) {
            $sqlString .= " INNER join page_id_to_template_id as ptotmpl" . $num . " on pprnt" . $num . ".id = ptotmpl" . $num . ".page_id and (";
            foreach ($typeIds as $key => $value) {
                if ($key != 0) {
                    $sqlString .= " OR";
                }
                $sqlString .= " ptotmpl" . $num . ".template_id = " . $value;
            }
            $sqlString .= ")";
        }
        return $sqlString;
    }

    private function sqlStringByFilters($filters, $num)
    {
        $sqlString = "";
        if (count($filters) > 0) {
            $numOfFilterProperty = 0;
            if (!$num) {
                $num = "";
            }
            foreach ($filters as $key => $value) {
                $numOfFilterProperty++;
                $tableNameOfProperties = "ftable" . $numOfFilterProperty . $num;
                $sqlString .= " INNER join filter_fields as " . $tableNameOfProperties . " on pprnt" . $num . ".id = " . $tableNameOfProperties . ".page_id and (";
				
				if($value["conditions"]) {
					$sqlString .= $this->getSqlFilterConditionsString($tableNameOfProperties, $value);
				} else{
					$sqlString .= $this->getSqlFilterFieldString($tableNameOfProperties, $value);
                }
				$sqlString .= ")";
            }
        }
		
        return $sqlString;
    }
	
	private function getSqlFilterConditionsString($tableNameOfProperties, $value) { 
		$sqlString .= "(";
		$conditionsLength = count($value["conditions"]);
		foreach ($value["conditions"] as $key => $value) {
			if($value["conditions"]){
				$sqlString .= $this->getSqlFilterConditionsString($tableNameOfProperties, $value);
			} else {
				$sqlString .= $this->getSqlFilterFieldString($tableNameOfProperties, $value);
				$sqlString .= " ";
				if ($conditionsLength - 1 !== $key) {
					$sqlString .= $value["condition"];
					$sqlString .= " ";
				}
			}
		}
		$sqlString .= ")";
		return $sqlString;
	}
	
	private function getSqlFilterFieldString($tableNameOfProperties, $value) {
		$sqlString .= " " . $tableNameOfProperties . ".field_name = '" . $value["name"] . "'";
		$fieldName = $value["dateType"] ? 'field_value_date' : 'field_value';
		$minValue = $value["dateType"] ? "'".$value["min"]."'" : $value["min"];
		$maxValue = $value["dateType"] ? "'".$value["max"]."'" : $value["max"];
		if (isset($value["min"]) && isset($value["max"])) {
			
			$sqlString .= " AND " . $tableNameOfProperties . ".".$fieldName." BETWEEN " . $minValue . " AND " . $maxValue;
		} else if (isset($value["min"])) {
			$sqlString .= " AND " . $tableNameOfProperties . ".".$fieldName." >= " . $minValue;
		} else if (isset($value["max"])) {
			$sqlString .= " AND " . $tableNameOfProperties . ".".$fieldName." <= " . $maxValue;
		} else if (isset($value["equal"])) {
			if (is_string($value["equal"])) {
				$sqlString .= " AND " . $tableNameOfProperties . ".field_value_string = '" . $value["equal"] . "'";
			} else {
				$value = $value["dateType"] ? "'".$value["equal"]."'" : $value["equal"];
				$sqlString .= " AND " . $tableNameOfProperties . ".".$fieldName." = " . $value;
			}
		} else if (isset($value["contains"])) {
			if (is_string($value["contains"])) {
				$sqlString .= " AND " . $tableNameOfProperties . ".field_value_string LIKE '%" . $value["contains"] . "%'";
			} else {
				$sqlString .= " AND (";
				foreach ($value["contains"] as $key => $value) {
					if (gettype($value) == "string") {
						$value = (int)$value;
					}

					if (is_int($value)) {

						if ($key != 0) {
							$sqlString .= " OR";
						}

						$sqlString .= " " . $tableNameOfProperties . ".field_value = " . $value;
					}
				}
				$sqlString .= ")";
			}
		}
		
		return $sqlString;
	}
	
	private function getSqlStringByMinMaxFilters($fieldNames, $tableTypeName, $postFix)
    {
        $sqlString = "";
        if (count($fieldNames) > 0) {
            foreach ($fieldNames as $key => $value) {
                $tableNameOfProperties = $tableTypeName . $postFix . $key;
                $sqlString .= " INNER join filter_fields as " . $tableNameOfProperties . " on pprnt1.id = " . $tableNameOfProperties . ".page_id and (";
                $sqlString .= " " . $tableNameOfProperties . ".field_name = '" . $value . "'";
                $sqlString .= ")";
            }
        }
        return $sqlString;
    }

    private function getSqlStringLimit($page, $limit)
    {
        $sqlLimit = "";
        if (is_int($limit)) {
            $sqlLimit .= " LIMIT ";
            if (is_int($page)) {
                $sqlLimit .= $page;
                $sqlLimit .= ", ";
            }

            $sqlLimit .= $limit;
        }
        return $sqlLimit;
    }

    private function getSqlStringForChildrenPages($depth, $id, $sortBySortNum)
    {
        $sqlString = "";
        if ((int)$depth > 1) {
            $brakets = " )";
            for ($i = 1; $i < (int)$depth; $i++) {
                $plusTwo = $i + 2;
                $plusOne = $i + 1;
				
                $sqlString .= " OR pprnt" . $i . ".parent_id IN (
						SELECT pprnt" . $plusTwo . ".id AS id" . $plusOne . " FROM page_parent_id AS pprnt" . $plusOne . " 
						LEFT JOIN page_parent_id AS pprnt" . $plusTwo . " ON  pprnt" . $plusOne . ".id = pprnt" . $plusTwo . ".parent_id
						WHERE pprnt" . $plusOne . ".parent_id = " . $id;

                if ($sortBySortNum) {
                    $sqlString .= " ORDER BY pprnt" . $plusTwo . ".countSortNum";
                }

                if ($i === $depth - 1) {
                    $sqlString .= $brakets;
                } else {
                    $brakets .= ")";
                }
            }
        }
        return $sqlString;
    }

    private function getSqlPageInfoJoin($findNoActives, $noActiveOnly = false)
    {
        $sqlPageInfoJoin = "
				INNER JOIN page_id_name pgname ON pprnt1.id = pgname.id 
				INNER JOIN page_id_uri pguri ON pgname.id = pguri.page_id 
				INNER JOIN page_id_to_template_id pgtmplt ON pprnt1.id = pgtmplt.page_id
                INNER JOIN page_id_active pgactv ON pprnt1.id = pgactv.id
                INNER JOIN dop_properties_page dpp ON pprnt1.id = dpp.id
			";
        if (!$findNoActives && !$noActiveOnly) {
            $sqlPageInfoJoin .= " AND pgactv.active=1";
        }
		
		if($noActiveOnly) {
			$sqlPageInfoJoin .= " AND pgactv.active=0";
		}
		
        return $sqlPageInfoJoin;
    }
}
