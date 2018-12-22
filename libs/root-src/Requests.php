<?php

class Requests
{

    public static function getUriMassive()
    {
        $explParams = explode("?", $_SERVER['REQUEST_URI']);//отсеиваем параметры
        return explode("/", $explParams[0]);
    }

    public static function getPUT()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            parse_str(file_get_contents('php://input'), $_PUT);
        }
        return $_PUT;
    }

    public static function getDELETE()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            parse_str(file_get_contents('php://input'), $_DELETE);
        }
        return $_DELETE;
    }

    public static function getFullUrl()
    {
        $actual_link = $_SERVER[HTTP_HOST];
        return $actual_link;
    }

    public static function getSubDomain()
    {
        $explodedString = explode('.', $_SERVER['HTTP_HOST']);
        if (count($explodedString) > 1) {
            return $explodedString[0];
        } else {
            return null;
        }
    }
}