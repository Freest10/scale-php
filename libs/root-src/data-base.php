<?php

class DataBase
{

    private $connectorParams = array();
    private static $sqlConnect = null;
    private $langs;
    private $language;

    public function __construct()
    {
        $this->connectorParams = MainConfiguration::getInstance()->getParsedIni()['connections'];
        $this->language = "en";
        $this->langs = \Langs::getInstance();
    }

    public function setConnectorParams($connectParams)
    {
        $this->connectorParams = $connectParams;
    }

    private function setLang()
    {
        $this->langs->setActiveLangByTextId($this->language);
    }

    public function connectToDataBase($notShowMessage = null)
    {
        self::$sqlConnect = @mysqli_connect($this->connectorParams['core.host'], $this->connectorParams['core.login'], $this->connectorParams['core.password'], $this->connectorParams['core.dbname']);
        //если дескриптор равен 0, соединение не установлено
        if (!self::$sqlConnect) {
            throw new \SystemException('', 'backend.errors.db_connect_error', 'modal');
        }

        $db_selected = self::$sqlConnect;
        if (!$db_selected) {
            throw new \SystemException('', 'backend.errors.db_select_error', 'modal');
        }
    }

    public function closeConnectToDataBase()
    {
        mysqli_close(self::$sqlConnect);
    }

    static function queryToDataBase($query, $typeArrayAssociated = MYSQLI_USE_RESULT)
    {
        $result = mysqli_query(self::$sqlConnect, $query);
        if ($result !== FALSE) {
            $result = mysqli_fetch_array($result, $typeArrayAssociated);
        }
        return $result;
    }

    static function justQueryToDataBase($query)
    {
        $resultQuery = mysqli_query(self::$sqlConnect, $query);
        return $resultQuery;
    }

    public static function countRowsTable($tableName)
    {
        $countRowsDbQuery = \DataBase::queryToDataBase("SELECT COUNT(*) FROM " . $tableName);
        return $countRowsDbQuery['COUNT(*)'];
    }

    public static function realEscapeString($data)
    {
        return mysqli_real_escape_string(self::$sqlConnect, $data);
    }

    public static function responseFromDataBase($resultQuery, $typeArrayAssociated = MYSQLI_ASSOC)
    {
        if (!$typeArrayAssociated) {
            $typeArrayAssociated = MYSQLI_ASSOC;
        }
        if ($resultQuery !== FALSE)
            $result = mysqli_fetch_array($resultQuery, $typeArrayAssociated);

        return $result;
    }

    public static function dropAllTablesAtDataBase()
    {
        \DataBase::justQueryToDataBase('SET foreign_key_checks = 0');
        if ($tables = \DataBase::justQueryToDataBase("SHOW TABLES")) {
            while ($row = \DataBase::responseFromDataBase($tables, MYSQLI_NUM)) {
                \DataBase::justQueryToDataBase('DROP TABLE IF EXISTS `'. $row[0].'`');
            }
        }
    }

    public static function createDataBase()
    {
        \DataBase::justQueryToDataBase("CREATE DATABASE `" . MainConfiguration::getInstance()->get("connections", "core.dbname") . "`");
    }
}
