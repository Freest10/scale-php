<?php

class Api extends ProxyApi
{
    private static $instance;
    private $uri_massive;

    function __construct()
    {
        $this->uri_massive = \Requests::getUriMassive();
    }

    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function reqToMainApiMethod()
    {
        $className = $this->uri_massive[4];
        $data = $this->uri_massive[5];
        if (!$className) {
            return false;
        }

        $upperCasedFirstLetterclassName = ucfirst($className);
        $transformedClassName = $this->getTransformedClassName($upperCasedFirstLetterclassName);
        $typeMethod = strtolower($_SERVER[REQUEST_METHOD]);
        if ($typeMethod == 'post') $typeMethod = 'set';
        $this->proxyCallMethod($transformedClassName, $typeMethod, $data);
    }

    public function reqToPluginApiMethod($className, $id, $pluginName)
    {
        if (!$className) {
            return false;
        }

        $upperCasedFirstLetterclassName = ucfirst($className);
        $transformedClassName = $this->getTransformedClassName($upperCasedFirstLetterclassName);
        $typeMethod = strtolower($_SERVER[REQUEST_METHOD]);
        if ($typeMethod == 'post') $typeMethod = 'set';
        $this->proxyCallPluginMethod($transformedClassName, $typeMethod, $pluginName, $id);
    }

    private function getTransformedClassName($upperCasedFirstLetterclassName)
    {
        return preg_replace_callback(
            "/\_[a-zA-Z]/",
            function ($match) {
                $splittedString = explode("_", $match[0]);
                return strtoupper($splittedString[1]);
            },
            $upperCasedFirstLetterclassName);
    }

}